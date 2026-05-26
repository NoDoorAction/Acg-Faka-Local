<?php
declare(strict_types=1);

namespace App\Service;

use Kernel\Annotation\Bind;

/**
 * Interface App
 * @package App\Service
 */
#[Bind(class: \App\Service\Bind\App::class)]
interface App
{
    /**
     * @return array
     */
    public function getVersions(): array;

    /**
     * 升级
     */
    public function update(): void;

    /**
     * 用本地/GitHub 下载的 zip 升级主程序。
     * @param string $zipPath  已下载到本地的源码 zip 绝对路径
     * @param string $targetVersion  目标版本号（写入 config/app.php）；留空时自动读取 zip 内 config/app.php 的 version 字段
     */
    public function updateFromZip(string $zipPath, string $targetVersion = ''): void;

    /**
     * 任务化升级 worker，由 UpgradeTask::dispatch() 在响应发回客户端后调度。
     * 内部按阶段读取/更新 runtime/upgrade/state.json，UI 通过 upgradeStatus 轮询展示进度。
     */
    public function runUpgradeTask(string $taskId): void;

    /**
     * 从指定的备份目录回滚 PHP 文件（不动数据库）。
     * @param string $backupDir 形如 kernel/Install/Backup/20260527142233 的相对路径或绝对路径
     */
    public function rollbackFromBackup(string $backupDir): void;

    /**
     *
     */
    public function upload(array $data): array;


    /**
     *
     */
    public function install(): void;


    /**
     * @param string $type
     * @return array
     */
    public function captcha(string $type): array;

    /**
     * @param string $username
     * @param string $password
     * @param string $captcha
     * @param array $cookie
     * @return array
     */
    public function register(string $username, string $password, string $captcha, array $cookie): array;

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login(string $username, string $password): array;

    /**
     * @param array $data
     * @return array
     */
    public function plugins(array $data): array;

    /**
     * @param int $type
     * @param int $pluginId
     * @param int $payType
     * @return array
     */
    public function purchase(int $type, int $pluginId, int $payType): array;

    /**
     * @return array
     */
    public function levels(): array;

    /**
     * @param int $authId
     * @return array
     */
    public function bindLevel(int $authId): array;

    /**
     * @param string $key
     * @param int $type
     * @param int $pluginId
     * @return void
     */
    public function installPlugin(string $key, int $type, int $pluginId): void;

    /**
     * @param string $key
     * @param int $type
     * @param int $pluginId
     */
    public function updatePlugin(string $key, int $type, int $pluginId): void;

    /**
     * @param string $key
     * @param int $type
     */
    public function uninstallPlugin(string $key, int $type): void;

    /**
     * @param int $pluginId
     * @return array
     */
    public function purchaseRecords(int $pluginId): array;

    /**
     * @param int $authId
     * @return array
     */
    public function unbind(int $authId): array;

    /**
     * @param array $data
     * @return array
     */
    public function developerPlugins(array $data): array;

    /**
     * @param array $data
     * @return array
     */
    public function developerCreatePlugin(array $data): array;

    /**
     * @param array $data
     * @return array
     */
    public function developerCreateKit(array $data): array;

    /**
     * 删除自己的插件
     * @param array $data
     * @return array
     */
    public function developerDeletePlugin(array $data): array;

    /**
     * @param array $data
     * @return array
     */
    public function developerUpdatePlugin(array $data): array;

    /**
     * @param array $data
     * @return array
     */
    public function developerPluginPriceSet(array $data): array;


    /**
     * @return array
     */
    public function service(): array;


    /**
     * @param array $data
     * @return array
     */
    public function editPassword(array $data): array;
}