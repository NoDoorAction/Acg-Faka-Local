# Plugin.php VM Bytecode

Source: `\kernel\Plugin.php`

Operand notes:

- `vN` means VM register N.
- Empty operand is shown as blank.
- String operands in this Markdown are previews; use JSON for exact bytes.

## _plugin_get_hwid

payload_len=887 inflated_len=2540 start_pc=43 instructions=90

```text
000 next=004 op=0xb55  "substr", v34, v19
001 next=073 op=0x1aa6 v6, v2, "ReflectionFunction", true
002 next=030 op=0xb55  "hash_hmac", v6, v11
003 next=065 op=0x1814 v25
004 next=066 op=0x1814 v12
005 next=019 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9", , v22
006 next=039 op=0xcb2  v10, , v17
007 next=008 op=0x7ad  v10, 1
008 next=063 op=0x1251 v20, v28, v6, "getEndLine"
009 next=081 op=0x1aa6 v6, v0, "ReflectionFunction", true
010 next=062 op=0xcb2  v10, , v7
011 next=067 op=0xb55  "implode", v34, v24
012 next=058 op=0xe58  v31, "\\xb5\\xd2\\x80"
013 next=044 op=0x1968 v10, v34
014 next=079 op=0xcb2  v34, , v19
015 next=031 op=0x1814 v21
016 next=084 op=0xe58  v29, "\\xd2\\x91\\xde"
017 next=023 op=0xb55  "md5", v34, v12
018 next=070 op=0x1814 v11
019 next=077 op=0xb55  "strpos", v6, v22
020 next=054 op=0xcb2  v34, , v21
021 next=057 op=0x1251 v23, v27, v6, "getEndLine"
022 next=082 op=0x1814 v7
023 next=068 op=0x10d7 v6
024 next=007 op=0x1aa6 v10, v14, v5, false
025 next=021 op=0x7ad  v10, 1
026 next=090 op=0x1603 v6, v34
027 next=026 op=0xe58  v6, "\\xe7\\xce\\xee"
028 next=016 op=0xe58  v8, "\\xc2\\xe6\\x9a"
029 next=035 op=0xb55  "implode", v6, v25
030 next=047 op=0x1814 v3
031 next=074 op=0xcb2  "\\x87\\xb9\\xe4U\\x1c \\x0b\\x16\\xb9+\\xca\\x17`\\x18\\x81\\xaa\\x83\\xb6\\x8f\\xb9\\x09\\xda!\\xa6\\x83\\x7fy\\xfc\\xcf\\x17\\xc9\\x7f\\xda\\x16O\\..." len=1632, , v21
032 next=038 op=0x1814 v22
033 next=052 op=0xcb2  v6, , v33
034 next=011 op=0xcb2  v34, , v24
035 next=033 op=0x1814 v33
036 next=037 op=0xcb2  v6, , v11
037 next=002 op=0xcb2  "\\x98C\\x06\\xef\\x8c\\xb1\\x86\\xf7\\xf2\\xb5\\x16H\\xa9{\\x15\\x8f\\xe1\\xd82e\\x07\\xbe%:\\xdf\\x94\\x0d\\x01\\x16n>_", , v11
038 next=005 op=0xcb2  v34, , v22
039 next=056 op=0xcb2  v18, , v17
040 next=083 op=0x1814 v24
041 next=042 op=0x1aa6 v34, v13, v26, false
042 next=055 op=0x1814 v15
043 next=012 op=0xe58  v16, "\\xf1\\xda\\xba"
044 next=071 op=0x1630 v10, 88
045 next=020 op=0xcb2  1, , v21
046 next=072 op=0xb55  "file", v32, v15
047 next=050 op=0xcb2  v6, , v3
048 next=029 op=0xcb2  v6, , v25
049 next=027 op=0x1603 v32, true
050 next=015 op=0xb55  "str_rot13", v6, v3
051 next=032 op=0xb55  "trim", v34, v4
052 next=018 op=0xb55  "trim", v6, v33
053 next=006 op=0xcb2  v32, , v17
054 next=013 op=0xb55  "openssl_decrypt", v34, v21
055 next=046 op=0xcb2  v34, , v15
056 next=040 op=0xb55  "array_slice", v34, v17
057 next=076 op=0x1aa6 v36, v23, v27, false
058 next=028 op=0xe58  v35, "\\x88\\xcb\\xa7"
059 next=025 op=0x1aa6 v10, v30, v1, false
060 next=045 op=0xcb2  v6, , v21
061 next=051 op=0xcb2  v34, , v4
062 next=085 op=0xcb2  v18, , v7
063 next=078 op=0x1aa6 v36, v20, v28, false
064 next=087 op=0xba6  
065 next=048 op=0xcb2  "", , v25
066 next=069 op=0xcb2  v34, , v12
067 next=061 op=0x1814 v4
068 next=086 op=0x1814 v0
069 next=017 op=0xcb2  true, , v12
070 next=036 op=0xcb2  "sha1", , v11
071 next=064 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
072 next=024 op=0x1251 v14, v5, v6, "getStartLine"
073 next=041 op=0x1251 v13, v26, v6, "getFileName"
074 next=060 op=0xcb2  "aes-256-cbc", , v21
075 next=088 op=0x1551 v32
076 next=022 op=0x1ac3 v18, v36, v10
077 next=014 op=0x1814 v19
078 next=080 op=0x1ac3 v18, v36, v10
079 next=000 op=0xcb2  v6, , v19
080 next=053 op=0x1814 v17
081 next=059 op=0x1251 v30, v1, v6, "getStartLine"
082 next=010 op=0xcb2  v32, , v7
083 next=034 op=0xcb2  "", , v24
084 next=075 op=0xe58  v9, "\\xaf\\xed\\xb6"
085 next=003 op=0xb55  "array_slice", v6, v7
086 next=009 op=0xcb2  v6, , v0
087 next=049 op=0xe58  v32, "\\xe7\\xc8\\x8a"
088 next=089 op=0x1814 v2
089 next=001 op=0xcb2  v32, , v2
```

## _plugin_aes_decrypt

payload_len=896 inflated_len=2524 start_pc=64 instructions=90

```text
000 next=031 op=0xcb2  v8, , v2
001 next=072 op=0xb55  "md5", v8, v2
002 next=057 op=0xe58  v3, "\\xc2\\xe6\\x9a"
003 next=083 op=0x1251 v27, v9, v7, "getStartLine"
004 next=056 op=0x7ad  v25, 1
005 next=030 op=0x1aa6 v7, v23, "ReflectionFunction", true
006 next=062 op=0xcb2  1, , v21
007 next=068 op=0xe58  v11, "\\xaf\\xed\\xb6"
008 next=090 op=0x1603 v7, v8
009 next=089 op=0x1814 v33
010 next=082 op=0x1814 v6
011 next=077 op=0x1251 v30, v10, v7, "getEndLine"
012 next=066 op=0x1814 v20
013 next=008 op=0xe58  v7, "\\xe7\\xce\\xee"
014 next=074 op=0xb55  "implode", v8, v35
015 next=023 op=0x1ac3 v28, v14, v25
016 next=012 op=0x1aa6 v8, v26, v13, false
017 next=078 op=0xb55  "hash_hmac", v7, v17
018 next=013 op=0x1603 v15, true
019 next=035 op=0xcb2  v7, , v12
020 next=010 op=0xb55  "array_slice", v7, v31
021 next=070 op=0xcb2  v15, , v5
022 next=076 op=0xcb2  v7, , v6
023 next=021 op=0x1814 v5
024 next=040 op=0x1251 v1, v4, v7, "getStartLine"
025 next=075 op=0xb55  "array_slice", v8, v5
026 next=085 op=0xcb2  v15, , v31
027 next=014 op=0xcb2  v8, , v35
028 next=045 op=0x1968 v25, v8
029 next=044 op=0xcb2  "{I`\\xe6\\xc8\\x09\\x9b\\xdc\\xef!\\xfb\\x02\\x084\\x8a!\\xc9\\xf1\\x0f>\\x04\\xbc\\x13\\xd8\\xf9dJ\\xfd]\\xc4\\x17\\xa0\\xa31\\x1e\\xcfr\\xdc\\xfa..." len=1248, , v21
030 next=016 op=0x1251 v26, v13, v7, "getFileName"
031 next=001 op=0xcb2  true, , v2
032 next=043 op=0xcb2  v7, , v17
033 next=081 op=0x1814 v23
034 next=020 op=0xcb2  v28, , v31
035 next=061 op=0xb55  "str_rot13", v7, v12
036 next=032 op=0xcb2  "sha1", , v17
037 next=027 op=0xcb2  "", , v35
038 next=018 op=0xe58  v15, "\\xe7\\xc8\\x8a"
039 next=071 op=0x1ac3 v28, v14, v25
040 next=004 op=0x1aa6 v25, v1, v4, false
041 next=086 op=0x1814 v36
042 next=073 op=0xcb2  v7, , v18
043 next=017 op=0xcb2  "7\\x04\\xad`\\xde\\xd1\\xc0\\xe7?\\xfeZ\\x94y6\\xa0(*\\xa3\\x12\\x89\\xf1\\x8fh\\xc1N\\xe2\\xf8\\xbdjw\\x9b\\xd2", , v17
044 next=053 op=0xcb2  "aes-256-cbc", , v21
045 next=063 op=0x1630 v25, 39
046 next=060 op=0x1814 v22
047 next=069 op=0xe58  v24, "\\xb5\\xd2\\x80"
048 next=011 op=0x7ad  v25, 1
049 next=003 op=0xb55  "file", v15, v20
050 next=028 op=0xb55  "openssl_decrypt", v8, v21
051 next=052 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8", , v33
052 next=046 op=0xb55  "strpos", v7, v33
053 next=006 op=0xcb2  v7, , v21
054 next=079 op=0xcb2  v8, , v19
055 next=038 op=0xba6  
056 next=080 op=0x1251 v32, v34, v7, "getEndLine"
057 next=007 op=0xe58  v16, "\\xd2\\x91\\xde"
058 next=042 op=0x1814 v18
059 next=036 op=0x1814 v17
060 next=084 op=0xcb2  v8, , v22
061 next=029 op=0x1814 v21
062 next=050 op=0xcb2  v8, , v21
063 next=055 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
064 next=047 op=0xe58  v29, "\\xf1\\xda\\xba"
065 next=000 op=0x1814 v2
066 next=049 op=0xcb2  v8, , v20
067 next=059 op=0xb55  "trim", v7, v36
068 next=033 op=0x1551 v15
069 next=002 op=0xe58  v0, "\\x88\\xcb\\xa7"
070 next=087 op=0xcb2  v25, , v5
071 next=026 op=0x1814 v31
072 next=058 op=0x10d7 v7
073 next=024 op=0x1aa6 v7, v18, "ReflectionFunction", true
074 next=054 op=0x1814 v19
075 next=037 op=0x1814 v35
076 next=041 op=0xb55  "implode", v7, v6
077 next=015 op=0x1aa6 v14, v30, v10, false
078 next=019 op=0x1814 v12
079 next=009 op=0xb55  "trim", v8, v19
080 next=039 op=0x1aa6 v14, v32, v34, false
081 next=005 op=0xcb2  v15, , v23
082 next=022 op=0xcb2  "", , v6
083 next=048 op=0x1aa6 v25, v27, v9, false
084 next=088 op=0xcb2  v7, , v22
085 next=034 op=0xcb2  v25, , v31
086 next=067 op=0xcb2  v7, , v36
087 next=025 op=0xcb2  v28, , v5
088 next=065 op=0xb55  "substr", v8, v22
089 next=051 op=0xcb2  v8, , v33
```

## _plugin_aes_encrypt

payload_len=892 inflated_len=2529 start_pc=33 instructions=90

```text
000 next=056 op=0xcb2  v2, , v21
001 next=059 op=0xcb2  "\\x92\\xec\\xf2kbt\\x8f\\xfe\\x02c\\xa1\\xa2\\x03\\xf5\\x93\\xa1\\x81\\x10\\xed\\x9ee\\xbbeq\\x9f\\xc6\\xa1\\xa7\\xed\\x03\\x9a\\x9b\\xdf$uU\\xb3\\x..." len=1456, , v26
002 next=011 op=0xb55  "str_rot13", v2, v9
003 next=014 op=0x1814 v1
004 next=060 op=0x1aa6 v2, v1, "ReflectionFunction", true
005 next=071 op=0x1251 v22, v10, v2, "getStartLine"
006 next=057 op=0xcb2  v3, , v18
007 next=028 op=0xb55  "md5", v33, v13
008 next=077 op=0xcb2  v2, , v36
009 next=069 op=0x1aa6 v7, v11, v27, false
010 next=072 op=0xcb2  v2, , v5
011 next=001 op=0x1814 v26
012 next=029 op=0xb55  "trim", v33, v25
013 next=003 op=0x1551 v35
014 next=004 op=0xcb2  v35, , v1
015 next=080 op=0xcb2  1, , v26
016 next=035 op=0xcb2  "", , v6
017 next=009 op=0x1251 v11, v27, v2, "getEndLine"
018 next=023 op=0x1814 v13
019 next=050 op=0xcb2  v33, , v19
020 next=073 op=0xcb2  v35, , v20
021 next=051 op=0x1814 v18
022 next=044 op=0x1814 v17
023 next=087 op=0xcb2  v33, , v13
024 next=049 op=0xb55  "array_slice", v2, v20
025 next=008 op=0x1814 v36
026 next=076 op=0x1814 v9
027 next=030 op=0x1aa6 v7, v23, v0, false
028 next=048 op=0x10d7 v2
029 next=063 op=0x1814 v28
030 next=034 op=0x1ac3 v14, v7, v3
031 next=015 op=0xcb2  v2, , v26
032 next=068 op=0xcb2  v33, , v24
033 next=038 op=0xe58  v4, "\\xf1\\xda\\xba"
034 next=020 op=0x1814 v20
035 next=081 op=0xcb2  v2, , v6
036 next=085 op=0x1814 v25
037 next=058 op=0x1aa6 v33, v29, v31, false
038 next=067 op=0xe58  v32, "\\xb5\\xd2\\x80"
039 next=066 op=0x1814 v5
040 next=047 op=0xe58  v12, "\\xd2\\x91\\xde"
041 next=046 op=0xba6  
042 next=052 op=0xb55  "openssl_decrypt", v33, v26
043 next=018 op=0xb55  "substr", v33, v24
044 next=086 op=0xcb2  "", , v17
045 next=017 op=0x7ad  v3, 1
046 next=064 op=0xe58  v35, "\\xe7\\xc8\\x8a"
047 next=013 op=0xe58  v34, "\\xaf\\xed\\xb6"
048 next=000 op=0x1814 v21
049 next=016 op=0x1814 v6
050 next=055 op=0xb55  "file", v35, v19
051 next=006 op=0xcb2  v35, , v18
052 next=083 op=0x1968 v3, v33
053 next=022 op=0xb55  "array_slice", v33, v18
054 next=041 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
055 next=082 op=0x1251 v30, v8, v2, "getStartLine"
056 next=005 op=0x1aa6 v2, v21, "ReflectionFunction", true
057 next=053 op=0xcb2  v14, , v18
058 next=019 op=0x1814 v19
059 next=031 op=0xcb2  "aes-256-cbc", , v26
060 next=037 op=0x1251 v29, v31, v2, "getFileName"
061 next=032 op=0x1814 v24
062 next=061 op=0xb55  "strpos", v2, v28
063 next=088 op=0xcb2  v33, , v28
064 next=089 op=0x1603 v35, true
065 next=024 op=0xcb2  v14, , v20
066 next=010 op=0xcb2  "sha1", , v5
067 next=078 op=0xe58  v16, "\\x88\\xcb\\xa7"
068 next=043 op=0xcb2  v2, , v24
069 next=021 op=0x1ac3 v14, v7, v3
070 next=074 op=0x7ad  v3, 1
071 next=070 op=0x1aa6 v3, v22, v10, false
072 next=079 op=0xcb2  "\\x98\\x0d{\\x12\\x06>m\\x07\\xf7\\x83\\x1c\\xbe\\xaeJ\\x9cU\\x13\\x0a\\xfa`~\\xcc\\x8bAU\\xedC\\xd8\\xe0\\xed\\xec\\xcc", , v5
073 next=065 op=0xcb2  v3, , v20
074 next=027 op=0x1251 v23, v0, v2, "getEndLine"
075 next=036 op=0xb55  "implode", v33, v17
076 next=002 op=0xcb2  v2, , v9
077 next=039 op=0xb55  "trim", v2, v36
078 next=040 op=0xe58  v15, "\\xc2\\xe6\\x9a"
079 next=026 op=0xb55  "hash_hmac", v2, v5
080 next=042 op=0xcb2  v33, , v26
081 next=025 op=0xb55  "implode", v2, v6
082 next=045 op=0x1aa6 v3, v30, v8, false
083 next=054 op=0x1630 v3, 47
084 next=090 op=0x1603 v2, v33
085 next=012 op=0xcb2  v33, , v25
086 next=075 op=0xcb2  v33, , v17
087 next=007 op=0xcb2  true, , v13
088 next=062 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1;$\\xd6", , v28
089 next=084 op=0xe58  v2, "\\xe7\\xce\\xee"
```

## _plugin_start

payload_len=887 inflated_len=2549 start_pc=69 instructions=90

```text
000 next=012 op=0x1814 v15
001 next=007 op=0xcb2  v2, , v1
002 next=016 op=0x10d7 v10
003 next=022 op=0xb55  "file", v13, v36
004 next=083 op=0x1ac3 v8, v6, v2
005 next=057 op=0x1814 v1
006 next=071 op=0x1251 v35, v7, v10, "getStartLine"
007 next=060 op=0xcb2  v8, , v1
008 next=076 op=0xe58  v14, "\\xc2\\xe6\\x9a"
009 next=032 op=0x1814 v36
010 next=024 op=0x1aa6 v6, v4, v30, false
011 next=084 op=0xcb2  v10, , v12
012 next=066 op=0xcb2  "", , v15
013 next=082 op=0x1551 v13
014 next=073 op=0xb55  "implode", v10, v33
015 next=021 op=0xb55  "strpos", v10, v20
016 next=034 op=0x1814 v19
017 next=062 op=0xe58  v13, "\\xe7\\xc8\\x8a"
018 next=040 op=0x1814 v0
019 next=002 op=0xb55  "md5", v16, v17
020 next=000 op=0xb55  "array_slice", v16, v27
021 next=074 op=0x1814 v31
022 next=059 op=0x1251 v29, v25, v10, "getStartLine"
023 next=090 op=0x1603 v10, v16
024 next=005 op=0x1ac3 v8, v6, v2
025 next=020 op=0xcb2  v8, , v27
026 next=011 op=0x1814 v12
027 next=043 op=0xcb2  v10, , v32
028 next=072 op=0x1251 v22, v28, v10, "getEndLine"
029 next=037 op=0xcb2  v10, , v31
030 next=041 op=0xcb2  "\\xe2\\x07j\\xa4o\\xa2%\\xb3\\xdd\\x01\\x8e+v(\\xd4\\x18~\\xbe\\x9c?\\xb1P3XBHU>=\\x8c\\xc7pA\\x8f\\xe9\\x1d\\x08\\xbe\\xfb\\x8dH\\x95\\x85\\x8e'..." len=3456, , v32
031 next=008 op=0xe58  v18, "\\x88\\xcb\\xa7"
032 next=003 op=0xcb2  v16, , v36
033 next=026 op=0xb55  "hash_hmac", v10, v0
034 next=080 op=0xcb2  v10, , v19
035 next=038 op=0x1630 v2, 18
036 next=063 op=0xb55  "implode", v16, v15
037 next=044 op=0xb55  "substr", v16, v31
038 next=046 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
039 next=009 op=0x1aa6 v16, v11, v34, false
040 next=056 op=0xcb2  "sha1", , v0
041 next=027 op=0xcb2  "aes-256-cbc", , v32
042 next=089 op=0xcb2  v16, , v32
043 next=042 op=0xcb2  1, , v32
044 next=081 op=0x1814 v17
045 next=028 op=0x7ad  v2, 1
046 next=017 op=0xba6  
047 next=025 op=0xcb2  v2, , v27
048 next=023 op=0xe58  v10, "\\xe7\\xce\\xee"
049 next=010 op=0x1251 v4, v30, v10, "getEndLine"
050 next=014 op=0xcb2  v10, , v33
051 next=054 op=0xb55  "trim", v16, v9
052 next=079 op=0xcb2  v16, , v20
053 next=049 op=0x7ad  v2, 1
054 next=052 op=0x1814 v20
055 next=075 op=0xcb2  v10, , v23
056 next=058 op=0xcb2  v10, , v0
057 next=001 op=0xcb2  v13, , v1
058 next=033 op=0xcb2  "\\xa2\\xc0ZR\\x8e\\xb9\\xfbA\\x1e\\x01Y\\x8d\\x99{\\xa5\\xa2m\\x0f\\x97\\xaf\\x97\\xf0\\xe3\\xd6^h\\x90\\xb6|\\x07t\\xc3", , v0
059 next=045 op=0x1aa6 v2, v29, v25, false
060 next=087 op=0xb55  "array_slice", v10, v1
061 next=047 op=0xcb2  v13, , v27
062 next=048 op=0x1603 v13, true
063 next=077 op=0x1814 v9
064 next=086 op=0xcb2  v13, , v26
065 next=050 op=0xcb2  "", , v33
066 next=036 op=0xcb2  v16, , v15
067 next=013 op=0xe58  v21, "\\xaf\\xed\\xb6"
068 next=019 op=0xcb2  true, , v17
069 next=078 op=0xe58  v3, "\\xf1\\xda\\xba"
070 next=039 op=0x1251 v11, v34, v10, "getFileName"
071 next=053 op=0x1aa6 v2, v35, v7, false
072 next=004 op=0x1aa6 v6, v22, v28, false
073 next=055 op=0x1814 v23
074 next=029 op=0xcb2  v16, , v31
075 next=018 op=0xb55  "trim", v10, v23
076 next=067 op=0xe58  v5, "\\xd2\\x91\\xde"
077 next=051 op=0xcb2  v16, , v9
078 next=031 op=0xe58  v24, "\\xb5\\xd2\\x80"
079 next=015 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1", , v20
080 next=006 op=0x1aa6 v10, v19, "ReflectionFunction", true
081 next=068 op=0xcb2  v16, , v17
082 next=064 op=0x1814 v26
083 next=061 op=0x1814 v27
084 next=085 op=0xb55  "str_rot13", v10, v12
085 next=030 op=0x1814 v32
086 next=070 op=0x1aa6 v10, v26, "ReflectionFunction", true
087 next=065 op=0x1814 v33
088 next=035 op=0x1968 v2, v16
089 next=088 op=0xb55  "openssl_decrypt", v16, v32
```

## _plugin_stop

payload_len=895 inflated_len=2524 start_pc=59 instructions=90

```text
000 next=081 op=0x1251 v6, v15, v8, "getEndLine"
001 next=016 op=0xb55  "substr", v7, v17
002 next=075 op=0xcb2  v8, , v21
003 next=012 op=0xcb2  v23, , v5
004 next=089 op=0xe58  v4, "\\xaf\\xed\\xb6"
005 next=074 op=0xe58  v16, "\\xb5\\xd2\\x80"
006 next=069 op=0x1814 v17
007 next=057 op=0x10d7 v8
008 next=058 op=0xb55  "implode", v8, v35
009 next=023 op=0x1aa6 v12, v9, v31, false
010 next=054 op=0xe58  v32, "\\xc2\\xe6\\x9a"
011 next=042 op=0x1814 v2
012 next=022 op=0xcb2  v12, , v5
013 next=082 op=0x1251 v18, v29, v8, "getEndLine"
014 next=090 op=0x1603 v8, v7
015 next=025 op=0x1814 v1
016 next=019 op=0x1814 v19
017 next=035 op=0xcb2  v12, , v34
018 next=003 op=0x1814 v5
019 next=046 op=0xcb2  v7, , v19
020 next=029 op=0xcb2  1, , v26
021 next=061 op=0xcb2  "\\x0f(\\x91\\xad\\xc57\\xbf\\x0e\\x98\\xb6\\x92\\x8c\\x09\\x86\\xf0\\xc0\\xa6'\\xaa\\x12)\\x0d\\xad\\xabfklcj\\xa7\\x89\\xaf\\xb0(\\xfa\\x90\\xb24\\..." len=1440, , v26
022 next=053 op=0xcb2  v13, , v5
023 next=013 op=0x7ad  v12, 1
024 next=037 op=0x1814 v27
025 next=076 op=0xcb2  "", , v1
026 next=021 op=0x1814 v26
027 next=024 op=0xb55  "trim", v7, v20
028 next=009 op=0x1251 v9, v31, v8, "getStartLine"
029 next=031 op=0xcb2  v7, , v26
030 next=086 op=0x1251 v11, v24, v8, "getFileName"
031 next=085 op=0xb55  "openssl_decrypt", v7, v26
032 next=047 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
033 next=087 op=0xcb2  "", , v35
034 next=006 op=0xb55  "strpos", v8, v27
035 next=071 op=0xcb2  v13, , v34
036 next=001 op=0xcb2  v8, , v17
037 next=066 op=0xcb2  v7, , v27
038 next=078 op=0x1814 v21
039 next=033 op=0x1814 v35
040 next=068 op=0xcb2  v23, , v28
041 next=050 op=0x1814 v20
042 next=049 op=0xcb2  v7, , v2
043 next=007 op=0xb55  "md5", v7, v19
044 next=077 op=0xcb2  v8, , v0
045 next=020 op=0xcb2  v8, , v26
046 next=043 op=0xcb2  true, , v19
047 next=048 op=0xba6  
048 next=083 op=0xe58  v23, "\\xe7\\xc8\\x8a"
049 next=028 op=0xb55  "file", v23, v2
050 next=027 op=0xcb2  v7, , v20
051 next=026 op=0xb55  "str_rot13", v8, v36
052 next=062 op=0x1814 v36
053 next=039 op=0xb55  "array_slice", v8, v5
054 next=004 op=0xe58  v10, "\\xd2\\x91\\xde"
055 next=041 op=0xb55  "implode", v7, v1
056 next=052 op=0xb55  "hash_hmac", v8, v21
057 next=070 op=0x1814 v33
058 next=044 op=0x1814 v0
059 next=005 op=0xe58  v30, "\\xf1\\xda\\xba"
060 next=017 op=0xcb2  v23, , v34
061 next=045 op=0xcb2  "aes-256-cbc", , v26
062 next=051 op=0xcb2  v8, , v36
063 next=065 op=0x1aa6 v12, v22, v25, false
064 next=060 op=0x1814 v34
065 next=000 op=0x7ad  v12, 1
066 next=034 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1;$", , v27
067 next=014 op=0xe58  v8, "\\xe7\\xce\\xee"
068 next=030 op=0x1aa6 v8, v28, "ReflectionFunction", true
069 next=036 op=0xcb2  v7, , v17
070 next=088 op=0xcb2  v8, , v33
071 next=015 op=0xb55  "array_slice", v7, v34
072 next=064 op=0x1ac3 v13, v3, v12
073 next=018 op=0x1ac3 v13, v3, v12
074 next=010 op=0xe58  v14, "\\x88\\xcb\\xa7"
075 next=056 op=0xcb2  "\\xcf\\x1f9\\xea\\x8c\\xc5R2\\xf4\\xfa\\xe2A\\x80\\xf1\\xb0\\xfc\\xbf\\xe1]\\xac\\x8c~N{\\x09\\x0drk_\\xd0\\xab\\xe4", , v21
076 next=055 op=0xcb2  v7, , v1
077 next=038 op=0xb55  "trim", v8, v0
078 next=002 op=0xcb2  "sha1", , v21
079 next=032 op=0x1630 v12, 49
080 next=040 op=0x1814 v28
081 next=073 op=0x1aa6 v3, v6, v15, false
082 next=072 op=0x1aa6 v3, v18, v29, false
083 next=067 op=0x1603 v23, true
084 next=063 op=0x1251 v22, v25, v8, "getStartLine"
085 next=079 op=0x1968 v12, v7
086 next=011 op=0x1aa6 v7, v11, v24, false
087 next=008 op=0xcb2  v8, , v35
088 next=084 op=0x1aa6 v8, v33, "ReflectionFunction", true
089 next=080 op=0x1551 v23
```

## _plugin_set_config

payload_len=889 inflated_len=2527 start_pc=7 instructions=90

```text
000 next=001 op=0xcb2  "aes-256-cbc", , v26
001 next=019 op=0xcb2  v4, , v26
002 next=030 op=0x1251 v20, v19, v4, "getEndLine"
003 next=076 op=0xe58  v32, "\\x88\\xcb\\xa7"
004 next=081 op=0x1814 v5
005 next=032 op=0xcb2  v25, , v12
006 next=025 op=0x1251 v16, v30, v4, "getFileName"
007 next=045 op=0xe58  v36, "\\xf1\\xda\\xba"
008 next=072 op=0x1814 v14
009 next=073 op=0x1814 v27
010 next=077 op=0x1814 v35
011 next=069 op=0x1603 v0, true
012 next=031 op=0xcb2  v25, , v24
013 next=085 op=0xcb2  v4, , v35
014 next=011 op=0xe58  v0, "\\xe7\\xc8\\x8a"
015 next=040 op=0xb55  "implode", v4, v3
016 next=052 op=0x1ac3 v22, v1, v28
017 next=036 op=0x1251 v33, v21, v4, "getStartLine"
018 next=070 op=0x1814 v3
019 next=027 op=0xcb2  1, , v26
020 next=016 op=0x1aa6 v1, v29, v6, false
021 next=065 op=0x1814 v17
022 next=015 op=0xcb2  v4, , v3
023 next=041 op=0x7ad  v28, 1
024 next=039 op=0x1814 v7
025 next=075 op=0x1aa6 v25, v16, v30, false
026 next=048 op=0xcb2  v28, , v8
027 next=055 op=0xcb2  v25, , v26
028 next=035 op=0xb55  "trim", v25, v31
029 next=080 op=0xe58  v15, "\\xaf\\xed\\xb6"
030 next=049 op=0x1aa6 v1, v20, v19, false
031 next=062 op=0xcb2  true, , v24
032 next=038 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1;$\\xd6", , v12
033 next=021 op=0xb55  "hash_hmac", v4, v35
034 next=066 op=0xb55  "array_slice", v25, v8
035 next=005 op=0x1814 v12
036 next=084 op=0x1aa6 v28, v33, v21, false
037 next=026 op=0xcb2  v0, , v8
038 next=024 op=0xb55  "strpos", v4, v12
039 next=083 op=0xcb2  v25, , v7
040 next=068 op=0x1814 v13
041 next=020 op=0x1251 v29, v6, v4, "getEndLine"
042 next=047 op=0xcb2  v22, , v14
043 next=050 op=0xcb2  v25, , v10
044 next=004 op=0x10d7 v4
045 next=003 op=0xe58  v34, "\\xb5\\xd2\\x80"
046 next=090 op=0x1603 v4, v25
047 next=018 op=0xb55  "array_slice", v4, v14
048 next=034 op=0xcb2  v22, , v8
049 next=008 op=0x1ac3 v22, v1, v28
050 next=054 op=0xb55  "file", v0, v10
051 next=006 op=0x1aa6 v4, v27, "ReflectionFunction", true
052 next=037 op=0x1814 v8
053 next=074 op=0xb55  "substr", v25, v7
054 next=078 op=0x1251 v23, v18, v4, "getStartLine"
055 next=060 op=0xb55  "openssl_decrypt", v25, v26
056 next=082 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
057 next=000 op=0xcb2  "\\xdd\\x05\\xcdw/\\x11q+\\x18\\xaa\\xa6\\xae\\xe86\\x8c\\x07\\xe0\\x94\\xcdq\\xd7\\x86\\xec]\\x8a\\xab\\x0cy\\xc5\\xed\\x1aE\\xb1e\\xb2/\\x15,\\xf7..." len=2192, , v26
058 next=029 op=0xe58  v9, "\\xd2\\x91\\xde"
059 next=028 op=0xcb2  v25, , v31
060 next=067 op=0x1968 v28, v25
061 next=057 op=0x1814 v26
062 next=044 op=0xb55  "md5", v25, v24
063 next=042 op=0xcb2  v28, , v14
064 next=059 op=0x1814 v31
065 next=088 op=0xcb2  v4, , v17
066 next=071 op=0x1814 v2
067 next=056 op=0x1630 v28, 15
068 next=079 op=0xcb2  v4, , v13
069 next=046 op=0xe58  v4, "\\xe7\\xce\\xee"
070 next=022 op=0xcb2  "", , v3
071 next=086 op=0xcb2  "", , v2
072 next=063 op=0xcb2  v0, , v14
073 next=051 op=0xcb2  v0, , v27
074 next=012 op=0x1814 v24
075 next=043 op=0x1814 v10
076 next=058 op=0xe58  v11, "\\xc2\\xe6\\x9a"
077 next=013 op=0xcb2  "sha1", , v35
078 next=023 op=0x1aa6 v28, v23, v18, false
079 next=010 op=0xb55  "trim", v4, v13
080 next=009 op=0x1551 v0
081 next=087 op=0xcb2  v4, , v5
082 next=014 op=0xba6  
083 next=053 op=0xcb2  v4, , v7
084 next=002 op=0x7ad  v28, 1
085 next=033 op=0xcb2  "Qe\\x90\\x9c-\\x0fz\\xb1\\xa6\\x14\\xd5\\x14\\xbfcY\\xb5\\xb9_\\xfbm\\x84\\xcc\\xb0B]l\\xb1\\xf8\\xf7\\xb0dK", , v35
086 next=089 op=0xcb2  v25, , v2
087 next=017 op=0x1aa6 v4, v5, "ReflectionFunction", true
088 next=061 op=0xb55  "str_rot13", v4, v17
089 next=064 op=0xb55  "implode", v25, v2
```

## _plugin_decrypt

payload_len=901 inflated_len=2529 start_pc=20 instructions=90

```text
000 next=026 op=0x1968 v18, v23
001 next=076 op=0x1814 v6
002 next=006 op=0xcb2  "\\x00G\\xb9uC\\xb2\\x7f\\x8fC\\xca\\xa6?\\xd6\\x00\\xdf\\xa0\\xf9\\xb5s\\x11\\x98X\\xba\\xa7a.l\\xb0~\\x1c\\xf21\\x0b\\xf4\\xf4\\xb3\\x87W\\xd9\\xf..." len=1424, , v7
003 next=089 op=0xb55  "trim", v3, v15
004 next=044 op=0xcb2  v28, , v8
005 next=013 op=0xcb2  "", , v14
006 next=025 op=0xcb2  "aes-256-cbc", , v7
007 next=039 op=0x7ad  v18, 1
008 next=028 op=0xcb2  v0, , v8
009 next=067 op=0xb55  "str_rot13", v3, v1
010 next=015 op=0xe58  v3, "\\xe7\\xce\\xee"
011 next=045 op=0x1251 v2, v35, v3, "getEndLine"
012 next=000 op=0xb55  "openssl_decrypt", v23, v7
013 next=016 op=0xcb2  v3, , v14
014 next=053 op=0x1814 v36
015 next=090 op=0x1603 v3, v23
016 next=029 op=0xb55  "implode", v3, v14
017 next=088 op=0x1251 v21, v11, v3, "getStartLine"
018 next=059 op=0xcb2  v0, , v10
019 next=080 op=0x1ac3 v28, v20, v18
020 next=022 op=0xe58  v12, "\\xf1\\xda\\xba"
021 next=054 op=0x1814 v25
022 next=071 op=0xe58  v16, "\\xb5\\xd2\\x80"
023 next=008 op=0x1814 v8
024 next=050 op=0xe58  v26, "\\xd2\\x91\\xde"
025 next=077 op=0xcb2  v3, , v7
026 next=040 op=0x1630 v18, 43
027 next=021 op=0x1aa6 v23, v13, v4, false
028 next=004 op=0xcb2  v18, , v8
029 next=052 op=0x1814 v15
030 next=086 op=0x1814 v29
031 next=066 op=0x1814 v19
032 next=061 op=0xcb2  v18, , v30
033 next=041 op=0xcb2  v23, , v22
034 next=007 op=0x1aa6 v18, v27, v9, false
035 next=087 op=0xcb2  v3, , v17
036 next=031 op=0xb55  "array_slice", v23, v30
037 next=064 op=0x1551 v0
038 next=012 op=0xcb2  v23, , v7
039 next=075 op=0x1251 v24, v32, v3, "getEndLine"
040 next=070 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
041 next=048 op=0xcb2  v3, , v22
042 next=072 op=0xe58  v0, "\\xe7\\xc8\\x8a"
043 next=049 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=", , v36
044 next=079 op=0xb55  "array_slice", v3, v8
045 next=019 op=0x1aa6 v20, v2, v35, false
046 next=014 op=0xb55  "trim", v23, v6
047 next=074 op=0xcb2  true, , v29
048 next=030 op=0xb55  "substr", v23, v22
049 next=078 op=0xb55  "strpos", v3, v36
050 next=037 op=0xe58  v5, "\\xaf\\xed\\xb6"
051 next=062 op=0xcb2  "sha1", , v33
052 next=003 op=0xcb2  v3, , v15
053 next=043 op=0xcb2  v23, , v36
054 next=056 op=0xcb2  v23, , v25
055 next=082 op=0x1814 v1
056 next=017 op=0xb55  "file", v0, v25
057 next=024 op=0xe58  v34, "\\xc2\\xe6\\x9a"
058 next=011 op=0x7ad  v18, 1
059 next=085 op=0x1aa6 v3, v10, "ReflectionFunction", true
060 next=083 op=0xcb2  "$\\x03\\xc6y\\x1e\\xd8g\\xc9x\\xab\\x0d\\x99y0\\xae\\xd9,da\\x9d\\xe5\\x13\\x10\\xbeE\\x9dwz\\xcf5@z", , v33
061 next=036 op=0xcb2  v28, , v30
062 next=060 op=0xcb2  v3, , v33
063 next=001 op=0xb55  "implode", v23, v19
064 next=018 op=0x1814 v10
065 next=035 op=0x1814 v17
066 next=081 op=0xcb2  "", , v19
067 next=002 op=0x1814 v7
068 next=023 op=0x1ac3 v28, v20, v18
069 next=065 op=0x10d7 v3
070 next=042 op=0xba6  
071 next=057 op=0xe58  v31, "\\x88\\xcb\\xa7"
072 next=010 op=0x1603 v0, true
073 next=034 op=0x1251 v27, v9, v3, "getStartLine"
074 next=069 op=0xb55  "md5", v23, v29
075 next=068 op=0x1aa6 v20, v24, v32, false
076 next=046 op=0xcb2  v23, , v6
077 next=038 op=0xcb2  1, , v7
078 next=033 op=0x1814 v22
079 next=005 op=0x1814 v14
080 next=084 op=0x1814 v30
081 next=063 op=0xcb2  v23, , v19
082 next=009 op=0xcb2  v3, , v1
083 next=055 op=0xb55  "hash_hmac", v3, v33
084 next=032 op=0xcb2  v0, , v30
085 next=027 op=0x1251 v13, v4, v3, "getFileName"
086 next=047 op=0xcb2  v23, , v29
087 next=073 op=0x1aa6 v3, v17, "ReflectionFunction", true
088 next=058 op=0x1aa6 v18, v21, v11, false
089 next=051 op=0x1814 v33
```

## _plugin_encrypt

payload_len=901 inflated_len=2538 start_pc=81 instructions=90

```text
000 next=017 op=0x1aa6 v26, v35, v22, false
001 next=031 op=0xcb2  v27, , v33
002 next=056 op=0x1aa6 v0, v3, v20, false
003 next=043 op=0x1aa6 v14, v16, "ReflectionFunction", true
004 next=075 op=0xcb2  v14, , v1
005 next=057 op=0xe58  v27, "\\xe7\\xc8\\x8a"
006 next=019 op=0xb55  "md5", v0, v21
007 next=040 op=0x1814 v2
008 next=001 op=0x1814 v33
009 next=087 op=0xe58  v36, "\\xaf\\xed\\xb6"
010 next=033 op=0xcb2  v26, , v34
011 next=062 op=0xb55  "implode", v0, v12
012 next=060 op=0xcb2  "D\\x81j\\xe1\\x96\\x15\\x8b\\xd9\\xc0\\xf8I.F\\x9bIKo\\xb9@\\xcb\\x11\\xc0\\x8fpB\\x9e?<l\\xfe4\\xcaM\\x04fh\\x81\\xde\\x1b\\xf36\\xf2b\\xa1\\x06..." len=1312, , v1
013 next=032 op=0xe58  v23, "\\x88\\xcb\\xa7"
014 next=027 op=0x1814 v4
015 next=041 op=0x1aa6 v6, v15, v18, false
016 next=021 op=0xcb2  "", , v12
017 next=050 op=0x7ad  v26, 1
018 next=054 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8", , v9
019 next=036 op=0x10d7 v14
020 next=009 op=0xe58  v13, "\\xd2\\x91\\xde"
021 next=011 op=0xcb2  v0, , v12
022 next=078 op=0xcb2  v0, , v29
023 next=058 op=0xcb2  v14, , v19
024 next=072 op=0xcb2  v0, , v1
025 next=053 op=0xcb2  v32, , v33
026 next=051 op=0x1814 v8
027 next=034 op=0xcb2  v0, , v4
028 next=046 op=0xcb2  v14, , v8
029 next=003 op=0xcb2  v27, , v16
030 next=068 op=0x1814 v10
031 next=025 op=0xcb2  v26, , v33
032 next=020 op=0xe58  v7, "\\xc2\\xe6\\x9a"
033 next=047 op=0xcb2  v32, , v34
034 next=039 op=0xcb2  v14, , v4
035 next=080 op=0x1968 v26, v0
036 next=044 op=0x1814 v5
037 next=084 op=0xe58  v14, "\\xe7\\xce\\xee"
038 next=015 op=0x1251 v15, v18, v14, "getEndLine"
039 next=063 op=0xb55  "substr", v0, v4
040 next=088 op=0xcb2  "sha1", , v2
041 next=008 op=0x1ac3 v32, v6, v26
042 next=061 op=0x1aa6 v14, v5, "ReflectionFunction", true
043 next=002 op=0x1251 v3, v20, v14, "getFileName"
044 next=042 op=0xcb2  v14, , v5
045 next=013 op=0xe58  v11, "\\xb5\\xd2\\x80"
046 next=030 op=0xb55  "implode", v14, v8
047 next=064 op=0xb55  "array_slice", v0, v34
048 next=029 op=0x1814 v16
049 next=089 op=0xb55  "hash_hmac", v14, v2
050 next=071 op=0x1251 v28, v31, v14, "getEndLine"
051 next=028 op=0xcb2  "", , v8
052 next=038 op=0x7ad  v26, 1
053 next=026 op=0xb55  "array_slice", v14, v33
054 next=014 op=0xb55  "strpos", v14, v9
055 next=076 op=0x1ac3 v32, v6, v26
056 next=022 op=0x1814 v29
057 next=037 op=0x1603 v27, true
058 next=069 op=0xb55  "str_rot13", v14, v19
059 next=006 op=0xcb2  true, , v21
060 next=004 op=0xcb2  "aes-256-cbc", , v1
061 next=077 op=0x1251 v17, v24, v14, "getStartLine"
062 next=070 op=0x1814 v25
063 next=086 op=0x1814 v21
064 next=016 op=0x1814 v12
065 next=073 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
066 next=083 op=0xb55  "trim", v0, v25
067 next=018 op=0xcb2  v0, , v9
068 next=074 op=0xcb2  v14, , v10
069 next=012 op=0x1814 v1
070 next=066 op=0xcb2  v0, , v25
071 next=055 op=0x1aa6 v6, v28, v31, false
072 next=035 op=0xb55  "openssl_decrypt", v0, v1
073 next=005 op=0xba6  
074 next=007 op=0xb55  "trim", v14, v10
075 next=024 op=0xcb2  1, , v1
076 next=082 op=0x1814 v34
077 next=052 op=0x1aa6 v26, v17, v24, false
078 next=079 op=0xb55  "file", v27, v29
079 next=000 op=0x1251 v35, v22, v14, "getStartLine"
080 next=065 op=0x1630 v26, 6
081 next=045 op=0xe58  v30, "\\xf1\\xda\\xba"
082 next=010 op=0xcb2  v27, , v34
083 next=067 op=0x1814 v9
084 next=090 op=0x1603 v14, v0
085 next=049 op=0xcb2  "<\\xcbkK\\xaa\\xa8\\xae\\xd4,\\x92B\\x02X&\\xe6s\\x8cg\\x92s\\xaa/L\\x10\\xb4Ex\\\\xda\\x06y*", , v2
086 next=059 op=0xcb2  v0, , v21
087 next=048 op=0x1551 v27
088 next=085 op=0xcb2  v14, , v2
089 next=023 op=0x1814 v19
```

## _plugin_hook_del_handle

payload_len=885 inflated_len=2539 start_pc=81 instructions=90

```text
000 next=060 op=0xcb2  v27, , v14
001 next=044 op=0xcb2  v1, , v33
002 next=001 op=0xcb2  v34, , v33
003 next=015 op=0xcb2  v22, , v24
004 next=055 op=0x1603 v27, true
005 next=027 op=0x1251 v16, v15, v1, "getStartLine"
006 next=031 op=0x1251 v30, v20, v1, "getEndLine"
007 next=018 op=0xcb2  v1, , v31
008 next=037 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9", , v17
009 next=036 op=0x1251 v0, v11, v1, "getStartLine"
010 next=074 op=0xcb2  v1, , v23
011 next=090 op=0x1603 v1, v34
012 next=054 op=0xcb2  v1, , v6
013 next=070 op=0xb55  "openssl_decrypt", v34, v23
014 next=016 op=0x1814 v21
015 next=058 op=0xcb2  v32, , v24
016 next=085 op=0xcb2  v34, , v21
017 next=050 op=0xcb2  v34, , v7
018 next=053 op=0xb55  "trim", v1, v31
019 next=051 op=0x1251 v8, v18, v1, "getEndLine"
020 next=063 op=0x1814 v17
021 next=019 op=0x7ad  v22, 1
022 next=089 op=0xcb2  v34, , v35
023 next=045 op=0xcb2  v1, , v4
024 next=062 op=0xb55  "hash_hmac", v1, v4
025 next=046 op=0x1814 v6
026 next=013 op=0xcb2  v34, , v23
027 next=078 op=0x1aa6 v22, v16, v15, false
028 next=080 op=0x10d7 v1
029 next=041 op=0xcb2  v27, , v19
030 next=034 op=0xcb2  v1, , v12
031 next=075 op=0x1aa6 v2, v30, v20, false
032 next=028 op=0xb55  "md5", v34, v21
033 next=065 op=0xe58  v9, "\\x88\\xcb\\xa7"
034 next=061 op=0xb55  "str_rot13", v1, v12
035 next=040 op=0xcb2  v1, , v25
036 next=021 op=0x1aa6 v22, v0, v11, false
037 next=048 op=0xb55  "strpos", v1, v17
038 next=047 op=0x1551 v27
039 next=000 op=0x1814 v14
040 next=009 op=0x1aa6 v1, v25, "ReflectionFunction", true
041 next=076 op=0x1aa6 v1, v19, "ReflectionFunction", true
042 next=087 op=0x1814 v35
043 next=066 op=0x1ac3 v32, v2, v22
044 next=014 op=0xb55  "substr", v34, v33
045 next=024 op=0xcb2  "\\xa5s\\x98I\\xcd\\x08q\\x06\\x0c\\xf4\\xbc\\x14\\xd7\\xbcK\\x06\\xefMu\\xf0\\xdd\\x05V\\x91\\xa3\\x0b\\xce\\xd6\\xf9_5\\xae", , v4
046 next=012 op=0xcb2  "", , v6
047 next=029 op=0x1814 v19
048 next=002 op=0x1814 v33
049 next=010 op=0xcb2  "aes-256-cbc", , v23
050 next=020 op=0xb55  "trim", v34, v7
051 next=043 op=0x1aa6 v2, v8, v18, false
052 next=073 op=0xcb2  v32, , v14
053 next=064 op=0x1814 v4
054 next=079 op=0xb55  "implode", v1, v6
055 next=011 op=0xe58  v1, "\\xe7\\xce\\xee"
056 next=049 op=0xcb2  "\\xc9\\x11\\x91\\x16\\x9b\\x04\\xc3\\x80\\xf6\\x9b\\x1c!\\xc9\\xdd\\x1f\\xfa%\\xbb\\xff\\xd0\\xccc:\\xd4\\xe7\\x0bt\\x94\\xa8\\xd3\\xc8\\xde\\xaa\\xf..." len=1392, , v23
057 next=072 op=0x1aa6 v34, v26, v3, false
058 next=025 op=0xb55  "array_slice", v1, v24
059 next=088 op=0xcb2  v34, , v28
060 next=052 op=0xcb2  v22, , v14
061 next=056 op=0x1814 v23
062 next=030 op=0x1814 v12
063 next=008 op=0xcb2  v34, , v17
064 next=023 op=0xcb2  "sha1", , v4
065 next=069 op=0xe58  v36, "\\xc2\\xe6\\x9a"
066 next=083 op=0x1814 v24
067 next=038 op=0xe58  v5, "\\xaf\\xed\\xb6"
068 next=077 op=0xba6  
069 next=067 op=0xe58  v29, "\\xd2\\x91\\xde"
070 next=086 op=0x1968 v22, v34
071 next=068 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
072 next=059 op=0x1814 v28
073 next=042 op=0xb55  "array_slice", v34, v14
074 next=026 op=0xcb2  1, , v23
075 next=039 op=0x1ac3 v32, v2, v22
076 next=057 op=0x1251 v26, v3, v1, "getFileName"
077 next=004 op=0xe58  v27, "\\xe7\\xc8\\x8a"
078 next=006 op=0x7ad  v22, 1
079 next=007 op=0x1814 v31
080 next=035 op=0x1814 v25
081 next=084 op=0xe58  v10, "\\xf1\\xda\\xba"
082 next=017 op=0x1814 v7
083 next=003 op=0xcb2  v27, , v24
084 next=033 op=0xe58  v13, "\\xb5\\xd2\\x80"
085 next=032 op=0xcb2  true, , v21
086 next=071 op=0x1630 v22, 78
087 next=022 op=0xcb2  "", , v35
088 next=005 op=0xb55  "file", v27, v28
089 next=082 op=0xb55  "implode", v34, v35
```

## _plugin_hook_del

payload_len=884 inflated_len=2526 start_pc=4 instructions=90

```text
000 next=072 op=0xcb2  v3, , v0
001 next=005 op=0x1aa6 v24, v12, v4, false
002 next=049 op=0xcb2  v6, , v8
003 next=071 op=0x1630 v24, 71
004 next=025 op=0xe58  v1, "\\xf1\\xda\\xba"
005 next=075 op=0x7ad  v24, 1
006 next=054 op=0xb55  "file", v16, v9
007 next=067 op=0x1814 v21
008 next=076 op=0xcb2  v3, , v21
009 next=079 op=0x1814 v14
010 next=090 op=0x1603 v3, v6
011 next=070 op=0xba6  
012 next=065 op=0x1814 v33
013 next=085 op=0x1aa6 v32, v5, v15, false
014 next=068 op=0xb55  "array_slice", v6, v34
015 next=052 op=0xcb2  v16, , v34
016 next=086 op=0xe58  v27, "\\xaf\\xed\\xb6"
017 next=087 op=0x1814 v22
018 next=006 op=0xcb2  v6, , v9
019 next=083 op=0xcb2  v16, , v36
020 next=044 op=0xe58  v35, "\\xc2\\xe6\\x9a"
021 next=020 op=0xe58  v11, "\\x88\\xcb\\xa7"
022 next=063 op=0x1603 v16, true
023 next=062 op=0xcb2  "sha1", , v30
024 next=081 op=0xcb2  v16, , v13
025 next=021 op=0xe58  v25, "\\xb5\\xd2\\x80"
026 next=080 op=0xb55  "md5", v6, v22
027 next=074 op=0x1aa6 v6, v19, v31, false
028 next=035 op=0xb55  "openssl_decrypt", v6, v28
029 next=042 op=0xb55  "array_slice", v3, v36
030 next=015 op=0x1814 v34
031 next=077 op=0xb55  "implode", v6, v29
032 next=031 op=0xcb2  v6, , v29
033 next=073 op=0xb55  "trim", v3, v2
034 next=046 op=0xcb2  "\\x94\\xc3zB\\xd0\\x95\\xe85tr|\\xc7\\x03\\xd04\\xb993!.\\xf41\\xafn\\xa9p\\x1f\\x18K\\x01T\\x8dZ\\xce\\x85\\xc2\\xe2\\xaf\\x8a\\x887\\xc8\\xe1Hj..." len=1344, , v28
035 next=003 op=0x1968 v24, v6
036 next=059 op=0x1814 v2
037 next=000 op=0xcb2  "", , v0
038 next=019 op=0x1814 v36
039 next=057 op=0x1aa6 v32, v18, v17, false
040 next=034 op=0x1814 v28
041 next=040 op=0xb55  "str_rot13", v3, v23
042 next=037 op=0x1814 v0
043 next=066 op=0x1aa6 v24, v7, v20, false
044 next=016 op=0xe58  v26, "\\xd2\\x91\\xde"
045 next=001 op=0x1251 v12, v4, v3, "getStartLine"
046 next=084 op=0xcb2  "aes-256-cbc", , v28
047 next=045 op=0x1aa6 v3, v33, "ReflectionFunction", true
048 next=026 op=0xcb2  true, , v22
049 next=009 op=0xb55  "trim", v6, v8
050 next=061 op=0xcb2  1, , v28
051 next=032 op=0xcb2  "", , v29
052 next=064 op=0xcb2  v24, , v34
053 next=056 op=0x1814 v23
054 next=043 op=0x1251 v7, v20, v3, "getStartLine"
055 next=027 op=0x1251 v19, v31, v3, "getFileName"
056 next=041 op=0xcb2  v3, , v23
057 next=038 op=0x1ac3 v10, v32, v24
058 next=089 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8", , v14
059 next=033 op=0xcb2  v3, , v2
060 next=053 op=0xb55  "hash_hmac", v3, v30
061 next=028 op=0xcb2  v6, , v28
062 next=088 op=0xcb2  v3, , v30
063 next=010 op=0xe58  v3, "\\xe7\\xce\\xee"
064 next=014 op=0xcb2  v10, , v34
065 next=047 op=0xcb2  v3, , v33
066 next=078 op=0x7ad  v24, 1
067 next=008 op=0xcb2  v6, , v21
068 next=051 op=0x1814 v29
069 next=029 op=0xcb2  v10, , v36
070 next=022 op=0xe58  v16, "\\xe7\\xc8\\x8a"
071 next=011 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
072 next=036 op=0xb55  "implode", v3, v0
073 next=023 op=0x1814 v30
074 next=018 op=0x1814 v9
075 next=039 op=0x1251 v18, v17, v3, "getEndLine"
076 next=017 op=0xb55  "substr", v6, v21
077 next=002 op=0x1814 v8
078 next=013 op=0x1251 v5, v15, v3, "getEndLine"
079 next=058 op=0xcb2  v6, , v14
080 next=012 op=0x10d7 v3
081 next=055 op=0x1aa6 v3, v13, "ReflectionFunction", true
082 next=024 op=0x1814 v13
083 next=069 op=0xcb2  v24, , v36
084 next=050 op=0xcb2  v3, , v28
085 next=030 op=0x1ac3 v10, v32, v24
086 next=082 op=0x1551 v16
087 next=048 op=0xcb2  v6, , v22
088 next=060 op=0xcb2  "X\\xa2\\xc5\\x8bo\\xef<4M[L\\x99\\xeclzI\\xbfOQ\\x0d\\xd4\\x0dM\\x94\\x14\\x18\\xb5\\xa2\\x8d\\x1bV\\x11", , v30
089 next=007 op=0xb55  "strpos", v3, v14
```

## _plugin_hook_add_handle

payload_len=885 inflated_len=2522 start_pc=63 instructions=90

```text
000 next=062 op=0xe58  v17, "\\xd2\\x91\\xde"
001 next=037 op=0xb55  "str_rot13", v8, v21
002 next=015 op=0xcb2  "sha1", , v24
003 next=014 op=0xe58  v34, "\\x88\\xcb\\xa7"
004 next=035 op=0x1551 v11
005 next=070 op=0x1814 v28
006 next=082 op=0xcb2  v2, , v32
007 next=065 op=0x1251 v22, v12, v8, "getStartLine"
008 next=041 op=0x1aa6 v14, v3, v6, false
009 next=017 op=0xcb2  v2, , v23
010 next=085 op=0xb55  "implode", v2, v33
011 next=001 op=0xcb2  v8, , v21
012 next=048 op=0xb55  "trim", v8, v1
013 next=079 op=0xb55  "implode", v8, v28
014 next=000 op=0xe58  v35, "\\xc2\\xe6\\x9a"
015 next=029 op=0xcb2  v8, , v24
016 next=058 op=0x1814 v29
017 next=055 op=0xcb2  v8, , v23
018 next=028 op=0x7ad  v0, 1
019 next=008 op=0x1251 v3, v6, v8, "getEndLine"
020 next=057 op=0xcb2  "", , v33
021 next=081 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
022 next=027 op=0x1251 v10, v36, v8, "getStartLine"
023 next=006 op=0xcb2  1, , v32
024 next=069 op=0x10d7 v8
025 next=075 op=0xcb2  v0, , v29
026 next=042 op=0xb55  "array_slice", v2, v25
027 next=086 op=0x1aa6 v0, v10, v36, false
028 next=047 op=0x1251 v26, v27, v8, "getEndLine"
029 next=060 op=0xcb2  "`m\\xbb\\x87\\x02\"\\x940\\xdb\\x10TC\\xbd\\xcb\\xfb4\\xe9\\x1fq\\xc1\\x9agV\\x99\\xc7r\\xdc#\\x09D\\x83@", , v24
030 next=007 op=0xb55  "file", v11, v30
031 next=066 op=0xcb2  v2, , v9
032 next=005 op=0xb55  "array_slice", v8, v29
033 next=090 op=0x1603 v8, v2
034 next=067 op=0x1968 v0, v2
035 next=059 op=0x1814 v15
036 next=089 op=0xcb2  v2, , v5
037 next=073 op=0x1814 v32
038 next=040 op=0x1ac3 v19, v14, v0
039 next=036 op=0x1814 v5
040 next=056 op=0x1814 v25
041 next=016 op=0x1ac3 v19, v14, v0
042 next=020 op=0x1814 v33
043 next=012 op=0xcb2  v8, , v1
044 next=033 op=0xe58  v8, "\\xe7\\xce\\xee"
045 next=049 op=0x1aa6 v2, v20, v7, false
046 next=087 op=0xcb2  true, , v13
047 next=038 op=0x1aa6 v14, v26, v27, false
048 next=002 op=0x1814 v24
049 next=054 op=0x1814 v30
050 next=080 op=0xb55  "strpos", v8, v5
051 next=068 op=0xe58  v11, "\\xe7\\xc8\\x8a"
052 next=022 op=0x1aa6 v8, v31, "ReflectionFunction", true
053 next=088 op=0x1aa6 v8, v15, "ReflectionFunction", true
054 next=030 op=0xcb2  v2, , v30
055 next=064 op=0xb55  "substr", v2, v23
056 next=077 op=0xcb2  v11, , v25
057 next=010 op=0xcb2  v2, , v33
058 next=025 op=0xcb2  v11, , v29
059 next=053 op=0xcb2  v11, , v15
060 next=084 op=0xb55  "hash_hmac", v8, v24
061 next=003 op=0xe58  v18, "\\xb5\\xd2\\x80"
062 next=004 op=0xe58  v4, "\\xaf\\xed\\xb6"
063 next=061 op=0xe58  v16, "\\xf1\\xda\\xba"
064 next=076 op=0x1814 v13
065 next=018 op=0x1aa6 v0, v22, v12, false
066 next=039 op=0xb55  "trim", v2, v9
067 next=021 op=0x1630 v0, 52
068 next=044 op=0x1603 v11, true
069 next=083 op=0x1814 v31
070 next=072 op=0xcb2  "", , v28
071 next=026 op=0xcb2  v19, , v25
072 next=013 op=0xcb2  v8, , v28
073 next=078 op=0xcb2  "\\xa5\\xaa-\\x02\\xda\\x83,\\xca\\x90\\xa4r\\xb0):\\xd2\\xf9u\\xcbE0u\\x96\\x14\\xa9\\xce\\xf4\\x1d\\xc2\\xed\\xf6\\xd4\\xd7\\xe7G\\xba\\x88\\x8b\\x..." len=2352, , v32
074 next=023 op=0xcb2  v8, , v32
075 next=032 op=0xcb2  v19, , v29
076 next=046 op=0xcb2  v2, , v13
077 next=071 op=0xcb2  v0, , v25
078 next=074 op=0xcb2  "aes-256-cbc", , v32
079 next=043 op=0x1814 v1
080 next=009 op=0x1814 v23
081 next=051 op=0xba6  
082 next=034 op=0xb55  "openssl_decrypt", v2, v32
083 next=052 op=0xcb2  v8, , v31
084 next=011 op=0x1814 v21
085 next=031 op=0x1814 v9
086 next=019 op=0x7ad  v0, 1
087 next=024 op=0xb55  "md5", v2, v13
088 next=045 op=0x1251 v20, v7, v8, "getFileName"
089 next=050 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1;$\\xd6\\xb9\\xed\\xf2", , v5
```

## _plugin_hook_add

payload_len=891 inflated_len=2524 start_pc=47 instructions=90

```text
000 next=012 op=0xcb2  "aes-256-cbc", , v29
001 next=083 op=0xcb2  v2, , v16
002 next=005 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
003 next=027 op=0xb55  "strpos", v2, v18
004 next=032 op=0xcb2  v2, , v0
005 next=042 op=0xba6  
006 next=076 op=0x1ac3 v22, v13, v35
007 next=010 op=0xcb2  v35, , v6
008 next=087 op=0xe58  v25, "\\xc2\\xe6\\x9a"
009 next=031 op=0xb55  "openssl_decrypt", v3, v29
010 next=011 op=0xcb2  v22, , v6
011 next=066 op=0xb55  "array_slice", v3, v6
012 next=065 op=0xcb2  v2, , v29
013 next=067 op=0xcb2  "", , v12
014 next=055 op=0x1251 v28, v31, v2, "getEndLine"
015 next=044 op=0xcb2  v22, , v34
016 next=045 op=0x1814 v29
017 next=002 op=0x1630 v35, 43
018 next=016 op=0xb55  "str_rot13", v2, v10
019 next=034 op=0xcb2  v3, , v24
020 next=073 op=0x1aa6 v35, v15, v27, false
021 next=014 op=0x7ad  v35, 1
022 next=071 op=0xb55  "trim", v2, v23
023 next=033 op=0xe58  v9, "\\xaf\\xed\\xb6"
024 next=070 op=0xcb2  "", , v11
025 next=068 op=0x1251 v33, v19, v2, "getFileName"
026 next=082 op=0xb55  "hash_hmac", v2, v16
027 next=059 op=0x1814 v0
028 next=052 op=0x10d7 v2
029 next=081 op=0xb55  "implode", v3, v12
030 next=036 op=0x1814 v23
031 next=017 op=0x1968 v35, v3
032 next=049 op=0xb55  "substr", v3, v0
033 next=043 op=0x1551 v7
034 next=038 op=0xcb2  true, , v24
035 next=003 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9", , v18
036 next=022 op=0xcb2  v2, , v23
037 next=075 op=0xb55  "trim", v3, v20
038 next=028 op=0xb55  "md5", v3, v24
039 next=020 op=0x1251 v15, v27, v2, "getStartLine"
040 next=072 op=0xcb2  v7, , v21
041 next=015 op=0xcb2  v35, , v34
042 next=058 op=0xe58  v7, "\\xe7\\xc8\\x8a"
043 next=040 op=0x1814 v21
044 next=088 op=0xb55  "array_slice", v2, v34
045 next=000 op=0xcb2  "\\xbeo\\xb9'\\xb1\\x0b\\xfa\\x8f\\x9a\\xbb\\xbf&N\\x18\\xf1]*\\xf2\\x7f\\xe0\\xf2\\xf2\\x7f\\x10\\xa2\\xe4\\xc7\\xc0\\xd0\\x8d\\x0f\\x0f)\\x0c\\xdcu..." len=1472, , v29
046 next=009 op=0xcb2  v3, , v29
047 next=050 op=0xe58  v32, "\\xf1\\xda\\xba"
048 next=030 op=0xb55  "implode", v2, v11
049 next=019 op=0x1814 v24
050 next=054 op=0xe58  v1, "\\xb5\\xd2\\x80"
051 next=007 op=0xcb2  v7, , v6
052 next=060 op=0x1814 v30
053 next=039 op=0x1aa6 v2, v30, "ReflectionFunction", true
054 next=008 op=0xe58  v14, "\\x88\\xcb\\xa7"
055 next=086 op=0x1aa6 v13, v28, v31, false
056 next=041 op=0xcb2  v7, , v34
057 next=001 op=0xcb2  "sha1", , v16
058 next=079 op=0x1603 v7, true
059 next=004 op=0xcb2  v3, , v0
060 next=053 op=0xcb2  v2, , v30
061 next=084 op=0x1814 v5
062 next=037 op=0xcb2  v3, , v20
063 next=090 op=0x1603 v2, v3
064 next=006 op=0x1aa6 v13, v4, v26, false
065 next=046 op=0xcb2  1, , v29
066 next=013 op=0x1814 v12
067 next=029 op=0xcb2  v3, , v12
068 next=061 op=0x1aa6 v3, v33, v19, false
069 next=078 op=0xb55  "file", v7, v5
070 next=048 op=0xcb2  v2, , v11
071 next=057 op=0x1814 v16
072 next=025 op=0x1aa6 v2, v21, "ReflectionFunction", true
073 next=077 op=0x7ad  v35, 1
074 next=021 op=0x1aa6 v35, v36, v17, false
075 next=085 op=0x1814 v18
076 next=056 op=0x1814 v34
077 next=064 op=0x1251 v4, v26, v2, "getEndLine"
078 next=074 op=0x1251 v36, v17, v2, "getStartLine"
079 next=063 op=0xe58  v2, "\\xe7\\xce\\xee"
080 next=051 op=0x1814 v6
081 next=062 op=0x1814 v20
082 next=089 op=0x1814 v10
083 next=026 op=0xcb2  "\\xb9\\x92\\xa04\\xb2\\xee\\xa6o3@\\x08Y\\xcc\\x0a\\xe1\\xb5\\x135\\xb0X\\x84\\x98\\xf5b|\\xe9\\xb9E1E&\\xcb", , v16
084 next=069 op=0xcb2  v3, , v5
085 next=035 op=0xcb2  v3, , v18
086 next=080 op=0x1ac3 v22, v13, v35
087 next=023 op=0xe58  v8, "\\xd2\\x91\\xde"
088 next=024 op=0x1814 v11
089 next=018 op=0xcb2  v2, , v10
```

## _plugin_hook_exist_handle

payload_len=906 inflated_len=2563 start_pc=24 instructions=90

```text
000 next=022 op=0xb55  "trim", v29, v34
001 next=089 op=0xcb2  "sha1", , v13
002 next=003 op=0x1aa6 v21, v6, v17, false
003 next=086 op=0x7ad  v21, 1
004 next=076 op=0xb55  "file", v30, v10
005 next=038 op=0xcb2  v29, , v25
006 next=073 op=0xb55  "array_slice", v28, v11
007 next=078 op=0x1aa6 v21, v15, v35, false
008 next=000 op=0xcb2  v29, , v34
009 next=016 op=0xcb2  v28, , v18
010 next=077 op=0x1251 v3, v33, v29, "getFileName"
011 next=025 op=0xe58  v14, "\\xd2\\x91\\xde"
012 next=060 op=0xb55  "substr", v28, v19
013 next=005 op=0x1814 v25
014 next=040 op=0xb55  "implode", v29, v31
015 next=026 op=0xe58  v30, "\\xe7\\xc8\\x8a"
016 next=041 op=0xb55  "implode", v28, v18
017 next=030 op=0xcb2  "aes-256-cbc", , v22
018 next=081 op=0x1551 v30
019 next=064 op=0xcb2  v30, , v11
020 next=071 op=0x1aa6 v29, v4, "ReflectionFunction", true
021 next=090 op=0x1603 v29, v28
022 next=001 op=0x1814 v13
023 next=035 op=0x1814 v4
024 next=075 op=0xe58  v16, "\\xf1\\xda\\xba"
025 next=018 op=0xe58  v36, "\\xaf\\xed\\xb6"
026 next=028 op=0x1603 v30, true
027 next=043 op=0xcb2  "", , v31
028 next=021 op=0xe58  v29, "\\xe7\\xce\\xee"
029 next=072 op=0xcb2  v30, , v23
030 next=036 op=0xcb2  v29, , v22
031 next=012 op=0xcb2  v29, , v19
032 next=013 op=0xb55  "hash_hmac", v29, v13
033 next=056 op=0xb55  "openssl_decrypt", v28, v22
034 next=010 op=0x1aa6 v29, v26, "ReflectionFunction", true
035 next=020 op=0xcb2  v29, , v4
036 next=052 op=0xcb2  1, , v22
037 next=049 op=0x1ac3 v2, v20, v21
038 next=051 op=0xb55  "str_rot13", v29, v25
039 next=083 op=0xe58  v12, "\\x88\\xcb\\xa7"
040 next=008 op=0x1814 v34
041 next=061 op=0x1814 v24
042 next=063 op=0x1aa6 v20, v0, v27, false
043 next=014 op=0xcb2  v29, , v31
044 next=032 op=0xcb2  "\\xbd\\x91\\x03\\x92\\xd2w\\x83_m\\xb1\\x864\\x0a\\x88\\x14~\\\\xa1k`\\x16\\xe2\\xd9\\x1ffj\\xc4]\\xc1\\x9d\\x84\\xf8", , v13
045 next=062 op=0x1814 v19
046 next=084 op=0xcb2  v28, , v7
047 next=067 op=0xb55  "md5", v28, v1
048 next=054 op=0xb55  "array_slice", v29, v23
049 next=029 op=0x1814 v23
050 next=034 op=0xcb2  v30, , v26
051 next=057 op=0x1814 v22
052 next=033 op=0xcb2  v28, , v22
053 next=087 op=0xb55  "trim", v28, v24
054 next=027 op=0x1814 v31
055 next=066 op=0x1630 v21, 16
056 next=055 op=0x1968 v21, v28
057 next=017 op=0xcb2  "\\x1e\\xe7\\xd96\\xa5@\\x89\\xa7q\\x0e\\xca\\xa5\\x0c0\\xd4\\xffeug\\xf6R\\xe6\\xa8\\x14_) \\x96s\\x0c\\x9d\\x0e\\xc9\\x1bX\\x00]N\\xcd\\xcf~\\xd4..." len=1232, , v22
058 next=037 op=0x1aa6 v20, v9, v5, false
059 next=015 op=0xba6  
060 next=068 op=0x1814 v1
061 next=053 op=0xcb2  v28, , v24
062 next=031 op=0xcb2  v28, , v19
063 next=069 op=0x1ac3 v2, v20, v21
064 next=079 op=0xcb2  v21, , v11
065 next=045 op=0xb55  "strpos", v29, v7
066 next=059 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
067 next=023 op=0x10d7 v29
068 next=074 op=0xcb2  v28, , v1
069 next=019 op=0x1814 v11
070 next=004 op=0xcb2  v28, , v10
071 next=002 op=0x1251 v6, v17, v29, "getStartLine"
072 next=085 op=0xcb2  v21, , v23
073 next=088 op=0x1814 v18
074 next=047 op=0xcb2  true, , v1
075 next=039 op=0xe58  v8, "\\xb5\\xd2\\x80"
076 next=007 op=0x1251 v15, v35, v29, "getStartLine"
077 next=080 op=0x1aa6 v28, v3, v33, false
078 next=082 op=0x7ad  v21, 1
079 next=006 op=0xcb2  v2, , v11
080 next=070 op=0x1814 v10
081 next=050 op=0x1814 v26
082 next=042 op=0x1251 v0, v27, v29, "getEndLine"
083 next=011 op=0xe58  v32, "\\xc2\\xe6\\x9a"
084 next=065 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1;$\\xd6\\xb9\\xed\\xf2", , v7
085 next=048 op=0xcb2  v2, , v23
086 next=058 op=0x1251 v9, v5, v29, "getEndLine"
087 next=046 op=0x1814 v7
088 next=009 op=0xcb2  "", , v18
089 next=044 op=0xcb2  v29, , v13
```

## _plugin_hook_exist

payload_len=891 inflated_len=2543 start_pc=3 instructions=90

```text
000 next=022 op=0xcb2  v5, , v16
001 next=019 op=0xcb2  v33, , v21
002 next=078 op=0x1aa6 v36, v27, v15, false
003 next=060 op=0xe58  v29, "\\xf1\\xda\\xba"
004 next=014 op=0xa0f  "DONT CHANGE ME OR YOU WILL BE FIRED"
005 next=015 op=0x1814 v0
006 next=013 op=0xe58  v26, "\\xe7\\xc8\\x8a"
007 next=056 op=0xe58  v3, "\\xd2\\x91\\xde"
008 next=063 op=0xb55  "array_slice", v33, v18
009 next=034 op=0x1aa6 v33, v6, "ReflectionFunction", true
010 next=017 op=0x1968 v32, v5
011 next=061 op=0xb55  "md5", v5, v24
012 next=077 op=0xb55  "hash_hmac", v33, v25
013 next=084 op=0x1603 v26, true
014 next=006 op=0xba6  
015 next=079 op=0xcb2  v26, , v0
016 next=045 op=0xb55  "trim", v5, v19
017 next=004 op=0x1630 v32, 7
018 next=002 op=0x1251 v27, v15, v33, "getEndLine"
019 next=035 op=0xb55  "trim", v33, v21
020 next=055 op=0x1814 v19
021 next=012 op=0xcb2  "\\x06\\xb0\\x16\\xf1\\x8d\\x89]\\x1bV\\x08\\xd52-h\\x0d\\xfe^l\\xe51d\\x99\\x07f\\xc6\\xf5\\x83\\x8a\\xb2\\xe52\\xb2", , v25
022 next=010 op=0xb55  "openssl_decrypt", v5, v16
023 next=040 op=0x1551 v26
024 next=025 op=0xcb2  v5, , v31
025 next=080 op=0xcb2  v33, , v31
026 next=008 op=0xcb2  v4, , v18
027 next=051 op=0xcb2  v33, , v23
028 next=020 op=0xb55  "implode", v5, v22
029 next=037 op=0xcb2  "aes-256-cbc", , v16
030 next=028 op=0xcb2  v5, , v22
031 next=075 op=0x1aa6 v5, v14, v34, false
032 next=031 op=0x1251 v14, v34, v33, "getFileName"
033 next=001 op=0x1814 v21
034 next=044 op=0x1251 v20, v2, v33, "getStartLine"
035 next=047 op=0x1814 v25
036 next=042 op=0x7ad  v32, 1
037 next=070 op=0xcb2  v33, , v16
038 next=052 op=0x1814 v24
039 next=009 op=0xcb2  v33, , v6
040 next=069 op=0x1814 v9
041 next=071 op=0x1aa6 v32, v13, v30, false
042 next=059 op=0x1251 v35, v11, v33, "getEndLine"
043 next=041 op=0x1251 v13, v30, v33, "getStartLine"
044 next=036 op=0x1aa6 v32, v20, v2, false
045 next=089 op=0x1814 v10
046 next=039 op=0x1814 v6
047 next=086 op=0xcb2  "sha1", , v25
048 next=072 op=0xcb2  v4, , v0
049 next=081 op=0xcb2  v5, , v8
050 next=032 op=0x1aa6 v33, v9, "ReflectionFunction", true
051 next=058 op=0xb55  "str_rot13", v33, v23
052 next=074 op=0xcb2  v5, , v24
053 next=054 op=0x1814 v18
054 next=067 op=0xcb2  v26, , v18
055 next=016 op=0xcb2  v5, , v19
056 next=023 op=0xe58  v17, "\\xaf\\xed\\xb6"
057 next=033 op=0xb55  "implode", v33, v1
058 next=088 op=0x1814 v16
059 next=068 op=0x1aa6 v36, v35, v11, false
060 next=073 op=0xe58  v12, "\\xb5\\xd2\\x80"
061 next=046 op=0x10d7 v33
062 next=057 op=0xcb2  v33, , v1
063 next=083 op=0x1814 v1
064 next=085 op=0xcb2  ";$\\xeb\\xc1\\x97\\x81\\x90\\xb1\\xb6\\xb8\\xc9\\xdd=1;$\\xd6\\xb9\\xed\\xf2", , v10
065 next=066 op=0x1814 v22
066 next=030 op=0xcb2  "", , v22
067 next=026 op=0xcb2  v32, , v18
068 next=053 op=0x1ac3 v4, v36, v32
069 next=050 op=0xcb2  v26, , v9
070 next=000 op=0xcb2  1, , v16
071 next=018 op=0x7ad  v32, 1
072 next=065 op=0xb55  "array_slice", v5, v0
073 next=076 op=0xe58  v28, "\\x88\\xcb\\xa7"
074 next=011 op=0xcb2  true, , v24
075 next=049 op=0x1814 v8
076 next=007 op=0xe58  v7, "\\xc2\\xe6\\x9a"
077 next=027 op=0x1814 v23
078 next=005 op=0x1ac3 v4, v36, v32
079 next=048 op=0xcb2  v32, , v0
080 next=038 op=0xb55  "substr", v5, v31
081 next=043 op=0xb55  "file", v26, v8
082 next=024 op=0x1814 v31
083 next=062 op=0xcb2  "", , v1
084 next=087 op=0xe58  v33, "\\xe7\\xce\\xee"
085 next=082 op=0xb55  "strpos", v33, v10
086 next=021 op=0xcb2  v33, , v25
087 next=090 op=0x1603 v33, v5
088 next=029 op=0xcb2  "\\x7fLm\\x17\\xe7\\xc2\\xdb\\x1c`\"\\x7f\\x8dU\\x09\\xcb\\x1eh,x,_\\x9c\\xfb\\xf4$\\x9fH\\xae|\\xee$\\xf3*bI\\\\xfa\\x03x\\xd2k^~p(\\x80\\x84\\x10..." len=1712, , v16
089 next=064 op=0xcb2  v5, , v10
```

