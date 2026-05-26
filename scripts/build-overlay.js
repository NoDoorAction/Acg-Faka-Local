#!/usr/bin/env node
/**
 * 构建覆盖包 (overlay package) —— 一个可以直接解压覆盖到已运行站点的 zip。
 *
 *   排除规则：
 *   - 用户私有：config/database.php, config/store.php
 *   - 运行时数据：runtime/, kernel/Install/{Lock,Backup,OS,Update}/
 *   - VCS / 构建产物：.git/, .github/, .idea/, .vscode/, dist/, node_modules/
 *   - 用户上传：assets/cache/general/, assets/cache/themes/upload/
 *   - 散文件：*.log, .env, .DS_Store, Thumbs.db, *.swp
 *   - 自身：scripts/build-overlay.js（避免循环）以及临时文件
 *   - 会话快照：2026-*-local-command-caveatcaveat-*.txt
 *
 * 输出：dist/acg-faka-{version}-overlay.zip
 *
 * 用法：
 *   node scripts/build-overlay.js                                # 自动读 config/app.php 的版本号
 *   node scripts/build-overlay.js --version 3.5.3                # 强制指定版本号
 *   node scripts/build-overlay.js --output dist/foo.zip          # 自定义输出文件
 *
 * 依赖：仅依赖 Node 内建模块 + PowerShell Compress-Archive (Windows) 或 zip (Linux/macOS)。
 */

const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const os = require('os');

const ROOT = path.resolve(__dirname, '..');

const EXCLUDE_PREFIXES = [
    'config/database.php',
    'config/store.php',
    'runtime',
    'kernel/Install/Lock',
    'kernel/Install/Backup',
    'kernel/Install/OS',
    'kernel/Install/Update',
    '.git',
    '.github',
    '.idea',
    '.vscode',
    '.fleet',
    'dist',
    'node_modules',
    'deobf',
    'assets/cache/general',
    'assets/cache/themes/upload',
];

const EXCLUDE_NAMES = new Set([
    '.env', '.env.local',
    '.DS_Store', 'Thumbs.db',
    'desktop.ini',
]);

const EXCLUDE_SUFFIXES = ['.log', '.swp', '.bak', '.orig'];

const EXCLUDE_PATTERNS = [
    /^2026-\d{2}-\d{2}-\d{6}-local-command-caveat.*\.txt$/i,
    /^scripts\/build-overlay\.js$/,
];

const FORCE_INCLUDE = new Set([
    'config/app.php',
]);

function readVersionFromConfig() {
    const cfg = path.join(ROOT, 'config', 'app.php');
    const content = fs.readFileSync(cfg, 'utf8');
    const m = content.match(/['"]version['"]\s*=>\s*['"]([^'"]+)['"]/);
    if (!m) throw new Error('无法从 config/app.php 读取 version');
    return m[1];
}

function parseArgs(argv) {
    const out = {};
    for (let i = 0; i < argv.length; i++) {
        if (argv[i] === '--version') out.version = argv[++i];
        else if (argv[i] === '--output') out.output = argv[++i];
    }
    return out;
}

function isExcluded(rel) {
    rel = rel.replace(/\\/g, '/');
    if (FORCE_INCLUDE.has(rel)) return false;
    const name = path.basename(rel);
    if (EXCLUDE_NAMES.has(name)) return true;
    for (const sfx of EXCLUDE_SUFFIXES) if (rel.endsWith(sfx)) return true;
    for (const pat of EXCLUDE_PATTERNS) if (pat.test(rel)) return true;
    for (const pre of EXCLUDE_PREFIXES) {
        if (rel === pre || rel.startsWith(pre + '/')) return true;
    }
    return false;
}

function walk(dir, base = '') {
    const out = [];
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const e of entries) {
        const full = path.join(dir, e.name);
        const rel = base ? `${base}/${e.name}` : e.name;
        if (isExcluded(rel)) continue;
        if (e.isDirectory()) {
            out.push(...walk(full, rel));
        } else if (e.isFile()) {
            out.push({ full, rel });
        }
    }
    return out;
}

function ensureDir(dir) {
    fs.mkdirSync(dir, { recursive: true });
}

function copyAllTo(files, stagingDir) {
    for (const f of files) {
        const dst = path.join(stagingDir, f.rel);
        ensureDir(path.dirname(dst));
        fs.copyFileSync(f.full, dst);
    }
}

function compressWindows(stagingDir, outZip) {
    if (fs.existsSync(outZip)) fs.unlinkSync(outZip);
    const psCmd = `Compress-Archive -Path '${stagingDir.replace(/'/g, "''")}/*' -DestinationPath '${outZip.replace(/'/g, "''")}' -CompressionLevel Optimal -Force`;
    const result = spawnSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', psCmd], { stdio: 'inherit' });
    if (result.status !== 0) throw new Error(`PowerShell Compress-Archive 失败 (exit ${result.status})`);
}

function compressUnix(stagingDir, outZip) {
    if (fs.existsSync(outZip)) fs.unlinkSync(outZip);
    const result = spawnSync('zip', ['-rq', outZip, '.'], { stdio: 'inherit', cwd: stagingDir });
    if (result.status !== 0) throw new Error(`zip 失败 (exit ${result.status})`);
}

function compress(stagingDir, outZip) {
    if (process.platform === 'win32') {
        compressWindows(stagingDir, outZip);
    } else {
        compressUnix(stagingDir, outZip);
    }
}

function rimraf(dir) {
    if (!fs.existsSync(dir)) return;
    fs.rmSync(dir, { recursive: true, force: true });
}

function formatSize(bytes) {
    const u = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    while (bytes >= 1024 && i < u.length - 1) { bytes /= 1024; i++; }
    return `${bytes.toFixed(2)} ${u[i]}`;
}

function main() {
    const args = parseArgs(process.argv.slice(2));
    const version = (args.version || readVersionFromConfig()).trim();
    const distDir = path.join(ROOT, 'dist');
    const stagingDir = path.join(distDir, `overlay-staging-${version}-${Date.now()}`);
    const outZip = path.resolve(args.output || path.join(distDir, `acg-faka-${version}-overlay.zip`));

    console.log(`[overlay] version: ${version}`);
    console.log(`[overlay] staging: ${stagingDir}`);
    console.log(`[overlay] output : ${outZip}`);

    ensureDir(distDir);
    rimraf(stagingDir);
    ensureDir(stagingDir);

    console.log('[overlay] scanning source...');
    const files = walk(ROOT);
    console.log(`[overlay] files to include: ${files.length}`);

    console.log('[overlay] staging files...');
    copyAllTo(files, stagingDir);

    console.log('[overlay] compressing...');
    compress(stagingDir, outZip);

    rimraf(stagingDir);

    const stat = fs.statSync(outZip);
    console.log(`[overlay] DONE: ${outZip} (${formatSize(stat.size)}, ${files.length} files)`);
}

main();
