!function () {
    const table = new Table("/admin/api/app/plugins", "#plugin-table");

    function _LocalInstall() {
        let uploadedPath = "";

        component.popup({
            submit: (data, _index) => {
                if (!uploadedPath) {
                    message.error("请先上传 zip 安装包");
                    return false;
                }
                if (!data.plugin_key) {
                    message.error("请填写插件标识");
                    return false;
                }
                util.post('/admin/api/app/localInstall', {
                    type: data.type,
                    plugin_key: data.plugin_key,
                    path: uploadedPath
                }, res => {
                    layer.close(_index);
                    message.success(res.msg || "安装完成");
                    table.refresh();
                });
            },
            tab: [
                {
                    name: `<i class="fa-duotone fa-regular fa-folder-arrow-up"></i> 本地 zip 安装`,
                    form: [
                        {
                            title: false,
                            name: "tips_page",
                            type: "custom",
                            complete: (form, dom) => {
                                dom.html(`<div class="alert alert-light border" style="background:#f8f9ff;">
                                    <p class="mb-1" style="font-size:13px;color:#333;">
                                        <i class="fa-duotone fa-regular fa-circle-info text-primary"></i>
                                        适用于内网/离线/私有 fork。上传插件 <b>zip 包</b>，系统会直接解压到目标目录并执行 install.sql。
                                    </p>
                                    <p class="mb-0" style="font-size:12px;color:#666;">
                                        zip 内根目录应为插件文件本身（如 <code>Config/Info.php</code>），不要嵌套子目录。
                                    </p>
                                </div>`);
                            }
                        },
                        {
                            title: "插件类型",
                            name: "type",
                            type: "select",
                            placeholder: "请选择插件类型",
                            dict: "_store_plugin_type"
                        },
                        {
                            title: "插件标识 (KEY)",
                            name: "plugin_key",
                            type: "input",
                            placeholder: "字母开头，仅支持字母/数字/下划线",
                            tips: "对应 Config/Info.php 中的 KEY 字段，安装后会创建 app/Plugin/{标识}/ 目录"
                        },
                        {
                            title: "安装包 (.zip)",
                            name: "zip_file",
                            type: "custom",
                            complete: (form, dom) => {
                                dom.html(`<div class="d-flex align-items-center" style="gap:10px;">
                                    <input type="file" accept=".zip" class="form-control local-zip-file"/>
                                    <span class="local-zip-state text-muted" style="font-size:12px;white-space:nowrap;">未选择</span>
                                </div>`);

                                const $file = dom.find(".local-zip-file");
                                const $state = dom.find(".local-zip-state");

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
                                        },
                                        error: () => {
                                            $state.text("上传失败").removeClass("text-muted").addClass("text-danger");
                                        }
                                    });
                                });
                            }
                        }
                    ]
                }
            ],
            confirmText: `<i class="fa-duotone fa-regular fa-folder-arrow-up"></i> 开始安装`,
            maxmin: false,
            autoPosition: true,
            width: "560px"
        });
    }

    function _RefreshIndex() {
        util.post('/admin/api/app/refreshPluginIndex', res => {
            message.info("已刷新插件索引");
            table.refresh();
        });
    }

    $(".btn-local-install").on("click", _LocalInstall);
    $(".btn-refresh-index").on("click", _RefreshIndex);

    // 类型筛选按钮：把 group 参数注入 table.queryParams，refresh 时会随 POST 带上
    $(".plugin-filter button").on("click", function () {
        const group = $(this).data("group");
        $(".plugin-filter button").removeClass("active btn-light-primary").addClass("btn-light");
        $(this).removeClass("btn-light").addClass("active btn-light-primary");

        // table.queryParams 是 bootstrap-table 在每次请求时合并的 map，直接修改它即可
        if (!table.queryParams) table.queryParams = {};
        if (parseInt(group) === -1) {
            delete table.queryParams.group;
        } else {
            table.queryParams.group = parseInt(group);
        }
        table.queryParams.page = 1;
        table.refresh(false);
    });

    table.setColumns([
        {
            field: 'plugin_name', title: '插件', formatter: function (val, item) {
                const icon = item.icon
                    ? `<img src="${item.icon}" class="table-item-icon" onerror="this.style.display='none'">`
                    : `<span class="table-item-icon" style="display:inline-flex;align-items:center;justify-content:center;background:#eef;border-radius:8px;color:#4a6cff;"><i class="fa-duotone fa-regular fa-puzzle-piece-simple"></i></span>`;
                return `<span class="table-item">${icon}<span class="table-item-name">${item.plugin_name}</span></span>`;
            }
        },
        {
            field: 'author', title: '作者', formatter: function (val, item) {
                if (!item.author) {
                    return `<span class="a-badge a-badge-light">-</span>`;
                }
                return `<span class="a-badge a-badge-success">${item.author}</span>`;
            }
        },
        {field: 'type', title: '类型', dict: '_store_plugin_type'},
        {field: 'description', title: '简介'},
        {
            field: 'homepage', title: '主页', formatter: function (val, item) {
                if (!item.homepage) return '-';
                return `<a href="${item.homepage}" target="_blank" class="text-primary"><i class="fa-duotone fa-regular fa-arrow-up-right-from-square"></i> GitHub</a>`;
            }
        },
        {
            field: 'version', title: '版本', formatter: function (val, item) {
                let html = `<span class="a-badge a-badge-secondary">${item.version}</span>`;
                if (item.install == 1) {
                    html += ` <span class="a-badge a-badge-success" title="已通过商店追踪"><i class="fa-duotone fa-regular fa-circle-check"></i> 已安装</span>`;
                } else if (item.install == 2) {
                    html += ` <span class="a-badge a-badge-warning" title="目录里有同名插件文件，但不是通过本商店安装的（可能是老版本异次元自带，或你 SFTP 直接传上去的）"><i class="fa-duotone fa-regular fa-circle-exclamation"></i> 本地预装 ${item.local_version ? '· v' + item.local_version : ''}</span>`;
                }
                return html;
            }
        },
        {
            field: 'operation', title: '操作', type: 'button', buttons: [
                {
                    icon: 'fa-duotone fa-regular fa-plus',
                    title: "安装",
                    show: item => item.install == 0,
                    class: "text-primary",
                    click: (event, value, row, index) => {
                        message.ask(`即将从 GitHub 仓库拉取并安装 <b style="color: mediumvioletred;">${row.plugin_name}</b>，是否继续？`, () => {
                            util.post('/admin/api/app/install', {
                                plugin_key: row.plugin_key,
                                type: row.type,
                                plugin_id: row.id
                            }, res => {
                                setTimeout(() => {
                                    table.refresh();
                                }, 500);

                                if (row.type == 1) {
                                    message.ask("支付插件安装成功，是否立即前往配置？", () => {
                                        window.location.href = "/admin/pay/plugin";
                                    }, `安装成功`, "前往支付扩展");
                                } else if (row.type == 2) {
                                    message.ask("网站模版安装成功，是否前往网站设置？", () => {
                                        window.location.href = "/admin/config/index";
                                    }, `安装成功`, "前往网站设置");
                                } else {
                                    message.ask("插件安装成功，是否前往插件管理？", () => {
                                        window.location.href = "/admin/plugin/index";
                                    }, `安装成功`, "前往插件管理");
                                }
                            });
                        }, "安装插件", "确认安装");
                    }
                },
                {
                    title: "更新",
                    show: item => item.install == 1,
                    class: "text-primary",
                    formatter: (item) => {
                        if (item.local_version && item.version !== item.local_version) {
                            return `<a type="button" class="a-badge-glass text-primary me-1 mb-1"><i class="fa-duotone fa-regular fa-arrows-rotate-reverse"></i> <span class="btn-title">更新( <span style='color: red;'>${item.local_version}</span> ➩ <b style='color: #28b728;'>${item.version}</b>)</span></a>`;
                        }
                    },
                    click: (event, value, row, index) => {
                        message.ask(row?.update_content?.replace(/\n/g, "<br>") || "无升级说明", () => {
                            util.post('/admin/api/app/upgrade', {
                                plugin_key: row.plugin_key,
                                type: row.type,
                                plugin_id: row.id
                            }, res => {
                                message.info(res.msg);
                                table.refresh();
                            });
                        }, `<b style="color: #1589e4;"><i class="fa-duotone fa-regular fa-sparkles"></i> ${row.plugin_name}</b> <span style="color: #0a84ff;font-size: 14px;">${row.local_version || '?'}</span> <i class="fa-duotone fa-regular fa-right-long text-danger"></i> <span style="color: green;font-size: 14px;">${row.version}</span>`, "立即更新");
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-trash-can',
                    title: "卸载",
                    show: item => item.install == 1,
                    class: "text-danger",
                    click: (event, value, row, index) => {
                        message.ask(`即将卸载 <b style="color: mediumvioletred;">${row.plugin_name}</b>，目录及其文件会被全部删除，是否继续？`, () => {
                            util.post('/admin/api/app/uninstall', {
                                plugin_key: row.plugin_key,
                                type: row.type
                            }, res => {
                                table.refresh();
                            });
                        }, "卸载插件", "确认卸载");
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-link',
                    title: "接管追踪",
                    show: item => item.install == 2,
                    class: "text-success",
                    click: (event, value, row, index) => {
                        message.ask(
                            `检测到 <b>${row.plugin_name}</b> 的文件已经存在，但不是通过本商店安装的。<br><br>` +
                            `点"确认接管"后会写入 <code>.faka-installed.json</code> 追踪标记，之后就能像普通商店插件一样<b>更新 / 卸载</b>。<br><br>` +
                            `<span class="text-muted" style="font-size:12px;">不会改任何业务代码或数据，只新建一个标记文件。</span>`,
                            () => {
                                util.post('/admin/api/app/claimPlugin', {
                                    plugin_key: row.plugin_key,
                                    type: row.type
                                }, res => {
                                    message.success(res.msg || "已接管");
                                    table.refresh();
                                });
                            }, "接管本地预装插件", "确认接管"
                        );
                    }
                },
                {
                    icon: 'fa-duotone fa-regular fa-trash-xmark',
                    title: "强制移除",
                    show: item => item.install == 2,
                    class: "text-danger",
                    click: (event, value, row, index) => {
                        message.ask(
                            `<b style="color:#d9534f;">这是本地预装插件，不是通过本商店安装的。</b><br><br>` +
                            `如果强制移除，<b>${row.plugin_name}</b> 的整个目录会被删除。<br>` +
                            `如果你不确定这个插件是不是业务在用的，建议先<b>接管追踪</b>而不是直接移除。<br><br>` +
                            `<span class="text-muted" style="font-size:12px;">如果文件不是 PHP 进程用户拥有，删除可能失败，会提示你怎么 chown。</span>`,
                            () => {
                                util.post('/admin/api/app/uninstall', {
                                    plugin_key: row.plugin_key,
                                    type: row.type
                                }, res => {
                                    table.refresh();
                                });
                            }, "强制移除本地预装插件", "确认移除"
                        );
                    }
                }
            ]
        }
    ]);

    table.setPagination(20, [20, 30, 50, 100, 200]);

    table.setSearch([
        {title: "搜索插件名称/简介..", name: "keywords", type: "input"}
    ]);

    table.render();
}();
