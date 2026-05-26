!function () {
    let _LatestVersion, _LocalVersion, _IsLatestVersion;

    function _RenderCloudUpdate(dom) {
        dom.html(`<div class="github-update">
            <div class="alert alert-light border" style="background:#f8f9ff;">
                <div class="d-flex align-items-center" style="gap:12px;">
                    <i class="fa-duotone fa-regular fa-cloud-arrow-down" style="font-size:28px;color:#2fcf94;"></i>
                    <div style="flex:1;">
                        <div style="font-size:13px;color:#666;">当前版本</div>
                        <div style="font-size:18px;color:#2fcf94;font-weight:600;" class="gh-local"></div>
                    </div>
                    <i class="fa-duotone fa-regular fa-arrow-right" style="color:#aaa;"></i>
                    <div style="flex:1;">
                        <div style="font-size:13px;color:#666;">目标版本</div>
                        <div style="font-size:18px;font-weight:600;" class="gh-latest">检测中...</div>
                    </div>
                </div>
            </div>

            <div class="gh-version-picker mb-3" style="display:none;">
                <label class="form-label" style="font-size:12px;color:#666;margin-bottom:4px;">
                    <i class="fa-duotone fa-regular fa-list-tree text-primary"></i> 选择要升级到的版本（默认最新，可挑任意 release 逐个升级）
                </label>
                <select class="form-select form-select-sm gh-version-select"></select>
            </div>

            <div class="gh-notes" style="background:#fafafa;border-radius:4px;padding:12px;max-height:280px;overflow:auto;font-size:13px;color:#444;white-space:pre-wrap;line-height:1.6;">加载中...</div>
            <div class="text-center mt-3">
                <a class="gh-link text-primary" target="_blank" style="font-size:12px;display:none;"><i class="fa-duotone fa-regular fa-arrow-up-right-from-square"></i> 在 GitHub 查看发布页</a>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary gh-upgrade-btn" style="min-width:220px;display:none;">
                    <i class="fa-duotone fa-regular fa-arrows-rotate"></i> 立即升级
                </button>
                <div class="gh-latest-tip text-success mt-2" style="display:none;font-size:13px;">
                    <i class="fa-duotone fa-regular fa-circle-check"></i> 你已经是最新版本
                </div>
                <div class="gh-overlay-badge mt-2" style="display:none;font-size:12px;color:#0d8a4f;background:#e8f7ee;border:1px solid #b8e6c8;border-radius:4px;padding:6px 10px;display:none;">
                    <i class="fa-duotone fa-regular fa-box-check"></i>
                    <b>覆盖包就绪</b>：本版本附带 <code>*-overlay.zip</code>，已剔除 config/database.php / config/store.php / runtime/ 等，可直接覆盖部署，无需手动剔除文件。
                </div>
            </div>
            <div class="text-muted mt-3" style="font-size:12px;line-height:1.7;">
                <i class="fa-duotone fa-regular fa-circle-info"></i> 系统会自动下载源码 zip → 覆盖代码 → 执行未应用的数据库迁移。<b>配置文件、用户数据、已装插件不会动</b>，升级前会自动备份关键目录至 <code>kernel/Install/Backup/</code>。<br>
                <i class="fa-duotone fa-regular fa-shield-check text-primary"></i> <b>大跨度升级兜底</b>：迁移管理器会按版本号顺序应用 <code>migrations/*.sql</code>；如担心一次跨太多版本出问题，可在上方下拉选择中间版本逐个升级。<br>
                <i class="fa-duotone fa-regular fa-box-check text-success"></i> 若版本带有"覆盖包就绪"标记，升级会自动选用 overlay zip（更安全、文件更少）。
            </div>
        </div>`);

        const $btn = dom.find(".gh-upgrade-btn");
        const $tip = dom.find(".gh-latest-tip");
        const $notes = dom.find(".gh-notes");
        const $latest = dom.find(".gh-latest");
        const $local = dom.find(".gh-local");
        const $link = dom.find(".gh-link");
        const $picker = dom.find(".gh-version-picker");
        const $select = dom.find(".gh-version-select");
        const $overlay = dom.find(".gh-overlay-badge");

        $local.text(_LocalVersion || "");

        // 拉所有 releases 渲染下拉
        util.post({
            url: "/admin/api/app/githubReleases", loader: false, error: false, fail: false,
            done: res => {
                if (res.code !== 200 || !Array.isArray(res.data) || res.data.length === 0) return;
                const rows = res.data;
                let opts = '';
                rows.forEach((r, idx) => {
                    const isLocal = r.version === _LocalVersion;
                    const flag = isLocal ? ' [当前]' : (idx === 0 ? ' [最新]' : '');
                    const beta = r.beta == 1 ? ' beta' : '';
                    const overlayTag = r.overlay == 1 ? ' 📦' : '';
                    const date = r.update_date ? ` · ${r.update_date}` : '';
                    opts += `<option value="${r.tag}" data-version="${r.version}" data-body="${encodeURIComponent(r.content || '')}" data-url="${r.update_url || ''}" data-overlay="${r.overlay || 0}" ${idx === 0 ? 'selected' : ''}>v${r.version}${beta}${overlayTag}${date}${flag}</option>`;
                });
                $select.html(opts);
                $picker.show();

                $select.off("change").on("change", function () {
                    const $opt = $(this).find("option:selected");
                    const version = $opt.data("version");
                    const tag = $(this).val();
                    const body = decodeURIComponent($opt.data("body") || "");
                    const url = $opt.data("url") || "";
                    const hasOverlay = String($opt.data("overlay")) === "1";
                    const isLocal = version === _LocalVersion;

                    $latest.text(version).css("color", isLocal ? "#2fcf94" : "#f98ee7");
                    $notes.html(body || "<i>该版本无发布说明</i>");
                    if (url) {
                        $link.attr("href", url).show();
                    } else {
                        $link.hide();
                    }
                    if (hasOverlay && !isLocal) {
                        $overlay.css("display", "block");
                    } else {
                        $overlay.hide();
                    }
                    if (isLocal) {
                        $btn.hide();
                        $tip.show();
                    } else {
                        $tip.hide();
                        const icon = hasOverlay ? "fa-box-check" : "fa-arrows-rotate";
                        const suffix = hasOverlay ? "（覆盖包）" : "";
                        $btn.html(`<i class="fa-duotone fa-regular ${icon}"></i> 立即升级到 v${version}${suffix}`).show();
                        $btn.data("tag", tag);
                    }
                }).trigger("change");
            }
        });

        // githubLatest 只用作首次没拿到 releases 列表时的兜底显示
        util.post({
            url: "/admin/api/app/githubLatest", loader: false, error: false, fail: false,
            done: res => {
                if (res.code != 200) {
                    if ($latest.text() === "检测中...") {
                        $latest.text("获取失败");
                        $notes.text(res.msg || "无法连接 GitHub");
                    }
                    return;
                }
                if ($select.find("option").length > 0) return; // 已有 releases 列表，跳过
                const d = res.data;
                const hasOverlay = String(d.overlay) === "1";
                $latest.text(d.version).css("color", d.latest ? "#2fcf94" : "#f98ee7");
                $notes.text(d.body || "（该版本无发布说明）");
                if (d.html_url) {
                    $link.attr("href", d.html_url).show();
                }
                if (hasOverlay && !d.latest) {
                    $overlay.css("display", "block");
                }
                if (d.latest) {
                    $tip.show();
                } else {
                    const icon = hasOverlay ? "fa-box-check" : "fa-arrows-rotate";
                    const suffix = hasOverlay ? "（覆盖包）" : "";
                    $btn.html(`<i class="fa-duotone fa-regular ${icon}"></i> 立即升级到 v${d.version}${suffix}`).show();
                    $btn.data("tag", d.tag || d.version);
                }
            }
        });

        $btn.off("click").on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const tag = $(this).data("tag");
            if (!tag) return false;
            message.ask(`即将从 GitHub 下载 <b>${tag}</b> 源码并覆盖部署，是否继续？`, () => {
                util.post({
                    url: "/admin/api/app/githubUpdate",
                    data: {tag: tag},
                    done: () => {
                        message.success("升级完成，页面即将刷新");
                        setTimeout(() => window.location.reload(), 1500);
                    }
                });
            });
            return false;
        });
    }

    function _RenderLocalUpdate(dom) {
        dom.html(`<div class="local-update">
            <div class="alert alert-warning d-flex align-items-center" style="background:#fffaf0;">
                <i class="fa-duotone fa-regular fa-circle-info" style="font-size:18px;margin-right:8px;"></i>
                <p class="mb-0" style="font-size:13px;">
                    适用于内网 / 离线 / 私有 fork 场景。请上传<b>完整源码 zip</b>（从 GitHub 仓库 "Code" → "Download ZIP" 得到的整包均可）。
                </p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">升级包 (.zip)</label>
                <div class="d-flex align-items-center" style="gap:10px;">
                    <input type="file" accept=".zip" class="form-control local-zip-file"/>
                    <span class="local-zip-state text-muted" style="font-size:12px;white-space:nowrap;">未选择</span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">目标版本号 <span class="text-muted" style="font-weight:normal;font-size:12px;">（可留空）</span></label>
                <input type="text" class="form-control local-zip-version" placeholder="留空将自动识别 zip 内 config/app.php 的 version"/>
                <div class="text-muted mt-1" style="font-size:12px;">
                    <i class="fa-duotone fa-regular fa-wand-magic-sparkles text-primary"></i> 默认会读取升级包根目录的 <code>config/app.php</code> 自动识别版本号；只在自动识别失败或想强制写入特定版本时才需要手动填写。
                </div>
            </div>

            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary local-zip-submit" style="min-width:220px;" disabled>
                    <i class="fa-duotone fa-regular fa-folder-arrow-up"></i> 开始升级
                </button>
            </div>
        </div>`);

        let uploadedPath = "";
        const $file = dom.find(".local-zip-file");
        const $state = dom.find(".local-zip-state");
        const $ver = dom.find(".local-zip-version");
        const $submit = dom.find(".local-zip-submit");

        function refresh() {
            $submit.prop("disabled", !uploadedPath);
        }

        $file.on("change", function () {
            const f = this.files && this.files[0];
            if (!f) return;
            const fd = new FormData();
            fd.append("file", f);
            $state.text("上传中...").removeClass("text-success text-danger").addClass("text-muted");
            $.ajax({
                url: "/admin/api/upload/send?mime=other",
                type: "POST",
                data: fd,
                contentType: false,
                processData: false,
                success: (res) => {
                    if (res.code != 200) {
                        $state.text(res.msg || "上传失败").removeClass("text-muted").addClass("text-danger");
                        return;
                    }
                    uploadedPath = res.data && res.data.path ? res.data.path : "";
                    $state.text("已上传").removeClass("text-muted").addClass("text-success");
                    refresh();
                },
                error: () => {
                    $state.text("上传失败").removeClass("text-muted").addClass("text-danger");
                }
            });
        });

        $ver.on("input", refresh);

        $submit.off("click").on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (!uploadedPath) return false;
            const version = $ver.val().trim();
            const tip = version
                ? `即将覆盖部署，并把当前版本写为 <b>${version}</b>，是否继续？`
                : `即将覆盖部署，<b>版本号将从升级包中自动识别</b>，是否继续？`;
            message.ask(tip, () => {
                util.post({
                    url: "/admin/api/app/localUpdate",
                    data: {path: uploadedPath, version: version},
                    done: () => {
                        message.success("升级完成，页面即将刷新");
                        setTimeout(() => window.location.reload(), 1500);
                    }
                });
            });
            return false;
        });
    }

    function _RenderVersionList(dom) {
        dom.html(`<div class="layui-timeline version-list"></div>
            <div class="text-center text-muted version-list-empty" style="display:none;font-size:13px;padding:30px 0;">
                <i class="fa-duotone fa-regular fa-cloud-slash"></i> 暂无可显示的发布版本
            </div>`);
        const $list = dom.find(".version-list");
        const $empty = dom.find(".version-list-empty");

        util.post({
            url: "/admin/api/app/githubReleases", done: res => {
                if (!res.data || res.data.length === 0) {
                    $empty.show();
                    return;
                }
                res.data.forEach(item => {
                    const beta = item?.beta == 1 ? `<b class="text-primary">beta</b>` : "<b class='text-success'>stable</b>";
                    $list.append(`<div class="layui-timeline-item">
                        <i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                        <div class="layui-timeline-content">
                            <h3 class="layui-timeline-title fs-5" style="color: ${item.version == _LocalVersion ? "#2fcf94" : "#f98ee7"};">${item.version} ${beta} ${item.version == _LocalVersion ? "←" : ''}</h3>
                            <p>${item.content}</p>
                            <p style="margin-top: 10px;color: #867d00;font-size: 12px;">source: <a class="text-primary" href="${item.update_url}" target="_blank">${item.tag || item.version}</a></p>
                            <p class="fw-normal" style="font-size: 12px;color: #009a25;">${item.update_date}</p>
                        </div>
                    </div>`);
                });
            }
        });
    }

    function _HandleUpdate(isUpdate) {
        component.popup({
            submit: false,
            width: "620px",
            height: "720px",
            maxmin: false,
            shadeClose: true,
            tab: [
                {
                    name: `<i class="fa-duotone fa-regular fa-cloud-arrow-down"></i> 一键升级`,
                    form: [{title: false, name: "cloud", type: "custom", complete: (form, dom) => _RenderCloudUpdate(dom)}]
                },
                {
                    name: `<i class="fa-duotone fa-regular fa-folder-arrow-up"></i> 上传 zip`,
                    form: [{title: false, name: "local", type: "custom", complete: (form, dom) => _RenderLocalUpdate(dom)}]
                },
                {
                    name: `<i class="fa-duotone fa-regular fa-code"></i> 版本列表`,
                    form: [{title: false, name: "list", type: "custom", complete: (form, dom) => _RenderVersionList(dom)}]
                }
            ]
        });
    }

    function _LodLatest() {
        util.post({
            url: "/admin/api/app/githubLatest",
            loader: false,
            done: res => {
                _LatestVersion = res.data.version;
                _LocalVersion = res.data.local;
                _IsLatestVersion = res.data.latest;

                $('.local-version').html(res.data.local);

                if (_IsLatestVersion) {
                    $('.latest-version').css("color", "green").html("[ Latest ]");
                } else {
                    $('.latest-version').css("color", "red").html(`[ 更新 v${res.data.version} ]`);
                    let cache = localStorage.getItem(res.data.version);
                    //第一次检测到版本，主动打开更新窗口
                    if (!cache) {
                        _HandleUpdate(true);
                        localStorage.setItem(res.data.version, true);
                    }
                }

                // off 一次，防御性地清掉任何旧脚本（含缓存的 _admin.js 旧 IIFE）可能绑过的 handler
                $('.latest-update').off("click").on("click", function () {
                    _HandleUpdate(!_IsLatestVersion);
                });
            },
            error: () => {
                $('.latest-update').css("color", "red").html("版本检查失败");
                $('.latest-update').off("click").click(() => _HandleUpdate(false));
            },
            fail: () => {
                $('.latest-update').css("color", "red").html("版本检查失败");
                $('.latest-update').off("click").click(() => _HandleUpdate(false));
            }
        });
    }

    function _LoadPluginUpdates() {
        $.get("/admin/api/app/getUpdates", res => {
            if (res.code != 200) {
                return;
            }

            if (res.data && Object.keys(res.data).length > 0) {
                localStorage.setItem("pluginVersions", JSON.stringify(res.data));
            }

            if (res.themePlugin > 0){
                $(`.theme-update`).html(`${res.themePlugin}个更新`).show();
            }

            if (res.generalPlugin > 0){
                $(`.general-update`).html(`${res.generalPlugin}个更新`).show();
            }

            if (res.payPlugin > 0){
                $(`.payPlugin-update`).html(`${res.payPlugin}个更新`).show();
            }
        });
    }

    function _Pjax() {
        $(document).pjax('a[target!=_blank]', '#pjax-container', {fragment: '#pjax-container', timeout: 8000});
        $(document).on('pjax:send', function () {
            Loading.show();
        });
        $(document).on('pjax:complete', function () {
            Loading.hide();
        });
        $("a[target!=_blank]").click(function () {
            $('a[target!=_blank]').removeClass("active");
            $(this).addClass("active");
        });
    }

    function _LoadSchemaHealth() {
        // 30 分钟内缓存一次结果，避免每次进后台都重查 information_schema
        const CACHE_KEY = "schemaHealth";
        const CACHE_TTL = 30 * 60 * 1000;
        const cached = (() => {
            try {
                const raw = localStorage.getItem(CACHE_KEY);
                if (!raw) return null;
                const obj = JSON.parse(raw);
                if (!obj || (Date.now() - obj.t) > CACHE_TTL) return null;
                return obj.d;
            } catch (e) { return null; }
        })();

        function render(d) {
            if (!d || d.ok) return;
            if ($('#schema-drift-banner').length) return;

            const tables = (d.missing_tables || []).map(t => `<code>${t}</code>`).join("、");
            const cols = Object.entries(d.missing_columns || {}).map(
                ([t, cs]) => `<code>${t}</code>: ${cs.map(c => `<code>${c}</code>`).join(", ")}`
            ).join("；");
            const parts = [];
            if (tables) parts.push(`缺失表：${tables}`);
            if (cols) parts.push(`缺失列：${cols}`);

            const sqlBlock = (d.suggested_sql || []).length
                ? `<details style="margin-top:8px;"><summary style="cursor:pointer;font-size:12px;color:#a05a00;">查看建议的 SQL（共 ${d.suggested_sql.length} 条，请审核后手动执行）</summary>
                     <pre style="background:#fff8e6;border:1px solid #ffe3a3;border-radius:4px;padding:8px;margin-top:6px;font-size:12px;max-height:280px;overflow:auto;white-space:pre-wrap;">${(d.suggested_sql || []).map(s => s.replace(/[&<>]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[ch]))).join("\n\n")}</pre>
                   </details>`
                : "";

            const banner = $(`<div id="schema-drift-banner" class="alert alert-warning" style="margin:12px 20px 0;border-left:4px solid #f0a020;">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <i class="fa-duotone fa-regular fa-triangle-exclamation" style="font-size:20px;color:#f0a020;margin-top:2px;"></i>
                    <div style="flex:1;">
                        <div style="font-weight:600;color:#a05a00;">数据库结构异常：本地 DB 与 <code>kernel/Install/Install.sql</code> 不一致</div>
                        <div style="margin-top:4px;font-size:13px;color:#7a4a00;">${parts.join("；")}</div>
                        <div style="margin-top:4px;font-size:12px;color:#9a6a00;">可能是版本升级时漏了对应的 <code>migrations/{version}.sql</code>，或安装迁移未跑完。建议在测试库验证后手动执行下方 SQL。</div>
                        ${sqlBlock}
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn-sm btn-light schema-drift-dismiss" style="font-size:12px;">我已知晓，今天不再提示</button>
                            <button type="button" class="btn btn-sm btn-light schema-drift-recheck" style="font-size:12px;">重新检测</button>
                        </div>
                    </div>
                </div>
            </div>`);

            const $host = $("#pjax-container").length ? $("#pjax-container") : $("body");
            $host.prepend(banner);

            banner.find(".schema-drift-dismiss").on("click", () => {
                localStorage.setItem("schemaHealthDismissed", String(Date.now()));
                banner.remove();
            });
            banner.find(".schema-drift-recheck").on("click", () => {
                localStorage.removeItem(CACHE_KEY);
                banner.remove();
                _LoadSchemaHealth();
            });
        }

        // 用户点过"今天不再提示"则跳过（24h 内）
        const dismissed = parseInt(localStorage.getItem("schemaHealthDismissed") || "0", 10);
        if (dismissed && (Date.now() - dismissed) < 24 * 3600 * 1000) return;

        if (cached) {
            render(cached);
            return;
        }
        util.post({
            url: "/admin/api/app/schemaCheck", loader: false, error: false, fail: false,
            done: res => {
                if (res.code !== 200) return;
                try { localStorage.setItem(CACHE_KEY, JSON.stringify({t: Date.now(), d: res.data})); } catch (e) {}
                render(res.data);
            }
        });
    }

    // 应用商店账号体系已脱钩，不再加载用户信息 / 服务节点切换
    _LodLatest();
    _LoadPluginUpdates();
    _LoadSchemaHealth();
    _Pjax();
}();