# Plugin.php 随手反编译。

目标文件：

```text
\kernel\Plugin.php
```


## 0. 结论

- 样本是 Z5Encrypt 风格的 PHP VM 混淆。
- 顶部层包含两个 `gzinflate(...)` 的数据区段。
- 第一个区段是常量池以及被加密字符串的材料。
- 第二个区段是 VM runtime / interpreter。
- `_plugin_*` 函数属于 VM wrapper，并不是直接的业务实现。
- wrapper 内部的 bytecode 可以被解压、可以被解析、也可以被反汇编。
- 恢复之后的业务逻辑是插件的启动停止、配置写入、Hook 缓存管理，以及 AES/Binary 的包装。

## 1. 分析约束

- 不执行原始的 `Plugin.php`。
- 不触发原始的 `eval`。
- 不加载未知的 payload。
- 不修改样本本身主体。
- 命名依据 bytecode、字符串表、runtime 行为和项目上下文来进行。

## 2. 文件结构

扫描命令：

```powershell
Select-String -LiteralPath .\Plugin.php -Pattern "gzinflate|function|eval"
```

结构：

| 位置 | 内容 | 作用 |
| --- | --- | --- |
| line 1 附近 | 非 ASCII 函数 + `gzinflate(...)` | 常量池 provider |
| line 132 附近 | 非 ASCII 函数 + `gzinflate(...)` | VM runtime provider |
| line 144-223 | `_plugin_*` 函数 | VM wrapper |

wrapper 的形状：

```php
$payload = "...compressed vm bytecode...";
$args = func_get_args();
$context = [$payload, __FILE__, __FUNCTION__, __CLASS__, __NAMESPACE__];
return eval(<runtime_provider>());
```

## 3. 顶层 inflate

| 来源位置 | 解压之后长度 | 作用 |
| --- | ---: | --- |
| line 1 附近 | `32453` bytes | 常量池、字符串材料、Z5Encrypt 标记 |
| line 132 附近 | `43809` bytes | VM runtime |

识别标记：

```text
Welcome+to+Z5Encrypt%3A+https%3A%2F%2Fz5encrypt.com%2F
Z5Encrypted at 17535351013265
DONT CHANGE ME OR YOU WILL BE FIRED
```

输出的文件：

```text
deob_gz_0.php
deob_gz_1.php
```

## 4. VM runtime

runtime 的读取流结构：

```php
$bytecode = $context[0];
$fh = fopen("php://filter/read=zlib.inflate/resource=php://memory", "r+");
fputs($fh, $bytecode);
rewind($fh);

$header = unpack("n*", fread($fh, 4));
$pc = $header[2];

fgetc($fh);
$bootstrap = fread($fh, $header[1] - 1);

while (true) {
    $raw = fread($fh, 10);
    if ($raw === "") break;
    $rec = unpack("Nk/na/Nz", $raw);
}
```

Header：

| 字段 | 类型 | 含义 |
| --- | --- | --- |
| `header[1]` | `uint16` | 和 bootstrap 长度相关的字段 |
| `header[2]` | `uint16` | 开始 PC |

Record：

```php
unpack("Nk/na/Nz", $raw)
```

| 字段 | 类型 | 含义 |
| --- | --- | --- |
| `k` | `uint32` | 记录 / 操作数块的尺寸 |
| `a` | `uint16` | 分支目标或者辅助 PC |
| `z` | `uint32` | opcode ID |

Operand：

```text
uint32 length + bytes
```

## 5. operand 类型

| tag | 含义 |
| --- | --- |
| `0x03` | float |
| `0x04` | int |
| `0x05` | false |
| `0x06` | true |
| `0x07` / `0x08` | string / encrypted string / expression |
| `0x0b` | 常量池引用 |

`0x0b` 对常量池进行引用：

```php
substr(<outer_payload_provider>(), $offset, $length)
```

字符串解密器：

```php
if ($data[0] == 0) {
    return substr($data, 1);
}

$keyLen = (int)($data[0]);
$key = substr($data, 0, $keyLen + 1);
$ciphertext = substr($data, $keyLen + 1);

return openssl_decrypt($ciphertext, "AES-128-ECB", $key, 1);
```

已经恢复的字符串包含：

```text
ReflectionFunction
getStartLine
getEndLine
getFileName
file
array_slice
implode
trim
strpos
substr
str_rot13
md5
sha1
hash_hmac
openssl_decrypt
aes-256-cbc
```

## 6. wrapper bytecode

导出命令：

```powershell
node .\export_plugin_vm_bytecode.js
```

输出：

```text
Plugin_vm_bytecode.json
Plugin_vm_bytecode.md
```

wrapper 列表：

| Wrapper | 状态 |
| --- | --- |
| `_plugin_get_hwid` | 可以 inflate，大约 90 records |
| `_plugin_aes_decrypt` | 可以 inflate，大约 90 records |
| `_plugin_aes_encrypt` | 可以 inflate，大约 90 records |
| `_plugin_start` | 可以 inflate，大约 90 records |
| `_plugin_stop` | 可以 inflate，大约 90 records |
| `_plugin_set_config` | 可以 inflate，大约 90 records |
| `_plugin_decrypt` | 可以 inflate，大约 90 records |
| `_plugin_encrypt` | 可以 inflate，大约 90 records |
| `_plugin_hook_del_handle` | 可以 inflate，大约 90 records |
| `_plugin_hook_del` | 可以 inflate，大约 90 records |
| `_plugin_hook_add_handle` | 可以 inflate，大约 90 records |
| `_plugin_hook_add` | 可以 inflate，大约 90 records |
| `_plugin_hook_exist_handle` | 可以 inflate，大约 90 records |
| `_plugin_hook_exist` | 可以 inflate，大约 90 records |

## 7. opcode 处理

处理的顺序：

1. 记录 opcode 的 operand 数量与类型。
2. 观察 stack / symbol table / PC 的变化。
3. 标记 PHP 原生调用。
4. 在多个 wrapper 里面进行交叉验证。
5. 给 opcode 一个临时名称。

行为类别：

| 类别 | 行为 |
| --- | --- |
| 变量 | 参数、本地变量、临时变量 |
| 数组 | 读取、写入、append、cast |
| 字符串 | concat、substr、trim、strpos |
| 调用 | function、method、static call |
| 控制流 | jump、branch、return |
| 动态执行 | eval、include、include_once |
| 反射 | ReflectionClass、ReflectionMethod、attributes |
| 全局状态 | `$GLOBALS` read/write |

动态入口：

```text
eval($...)
eval("return $...;")
include($...)
include_once($...)
call_user_func_array(...)
ReflectionClass(...)
ReflectionFunction(...)
$GLOBALS[...]
```

## 8. 项目上下文

用于语义验证的文件：

```text
kernel\Util\Binary.php
kernel\Util\Plugin.php
kernel\Plugin\Hook.php
app\Util\Aes.php
app\Controller\Admin\Api\Plugin.php
```

恢复映射：

| Wrapper | 语义 |
| --- | --- |
| `_plugin_get_hwid` | 生成环境绑定的 key |
| `_plugin_aes_decrypt` | AES-128-CBC 解密 |
| `_plugin_aes_encrypt` | AES-128-CBC 加密 |
| `_plugin_encrypt` | `Binary::inst()->pack` |
| `_plugin_decrypt` | `Binary::inst()->unpack` |
| `_plugin_set_config` | 写入配置 |
| `_plugin_start` | 启用插件 |
| `_plugin_stop` | 停用插件 |
| `_plugin_hook_add_handle` | 合并 Hook 定义 |
| `_plugin_hook_del_handle` | 删除 Hook 定义 |
| `_plugin_hook_exist_handle` | 查询 Hook |
| `_plugin_hook_add` | 写入指定插件 Hook |
| `_plugin_hook_del` | 删除指定插件 Hook |
| `_plugin_hook_exist` | 查询指定 Hook |

## 9. 关键还原

`_plugin_get_hwid`：

```php
$db = config('database');
$seed = $db['host'] . $db['database'] . $db['username'] . $db['prefix'] . __FILE__;
return strtoupper(substr(md5($seed), 0, 16));
```

`_plugin_aes_decrypt`：

```php
return openssl_decrypt($data, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
```

`_plugin_aes_encrypt`：

```php
return openssl_encrypt($data, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
```

`_plugin_encrypt`：

```php
return Binary::inst()->pack($data, _plugin_get_hwid());
```

`_plugin_decrypt`：

```php
return Binary::inst()->unpack($data, _plugin_get_hwid()) ?: [];
```

`_plugin_start`：

```text
检查插件目录
检查 Config/Info.php
读取 Config/Config.php
STATUS = 1
写入配置
添加 Hook
按照 check 参数决定是否运行 START hook
删除 runtime/plugin/plugin.cache
```

`_plugin_stop`：

```text
检查 Config/Config.php
读取配置
STATUS = 0
写入配置
运行 STOP hook
删除 Hook
删除 runtime/plugin/plugin.cache
```

## 10. 验证

结构验证：

```text
两个顶层段 inflate 成功
runtime 是可读的
wrapper bytecode 可以 inflate
record 格式可以稳定解析
```

字符串验证：

```text
BASE_PATH
Config/Config.php
Config/Info.php
runtime/plugin/plugin.cache
STATUS
START
STOP
ReflectionClass
ReflectionMethod
openssl_decrypt
```

上下文验证：

| 还原点 | 验证文件 |
| --- | --- |
| Binary pack/unpack | `kernel\Util\Binary.php` |
| 插件启停 | `kernel\Util\Plugin.php` |
| Hook cache | `kernel\Plugin\Hook.php` |
| AES 参数 | `app\Util\Aes.php` |

## 11. 迁移流程

同类样本的处理流程：

1. 扫描 `gzinflate`、`eval`、wrapper。
2. 解压顶层 payload。
3. 区分常量池和 runtime。
4. 分析 runtime 的 header / record / operand 格式。
5. 解压 wrapper VM stream。
6. 导出 bytecode JSON / Markdown。
7. 解析 operand tag。
8. 解密字符串表。
9. 建立 opcode 行为表。
10. 生成反汇编的中间表示。
11. 结合项目上下文恢复业务语义。
12. 单独审计动态执行入口。

## 12. 产物关系

| 文件 | 作用 |
| --- | --- |
| `Plugin.php` | 原始样本 |
| `deob_gz_0.php` | 常量池 |
| `deob_gz_1.php` | VM runtime |
| `Plugin_vm_bytecode.json` | bytecode 结构化结果 |
| `Plugin_vm_bytecode.md` | bytecode 人工审阅结果 |
| `Plugin_VM_analysis.md` | VM 结构分析 |
| `Plugin_recovered.md` | 语义恢复记录 |
| `Plugin_deobf.php` | 可读化 PHP |

## 13. 最终模型

保护模型：

```text
PHP wrapper
  -> compressed VM bytecode
  -> zlib inflate
  -> VM record parser
  -> operand resolver
  -> AES string resolver
  -> opcode dispatcher
  -> PHP dynamic bridge
```

业务模型：

```text
插件配置
  -> 状态写入
  -> Hook 注册 / 删除 / 查询
  -> 加密缓存读写
  -> 插件运行缓存清理
```
