# Perry PHP — IR 生成器覆盖率提升 — 发现报告

## 2026-05-09 差距分析结果

### IR 接口方法覆盖率

| 生成器 | 接口方法 | 实现 | 覆盖率 |
|--------|----------|------|--------|
| Swift | 90 | 90 | **100%** ✅ |
| Kotlin | 90 | 90 | **100%** ✅ |
| Dart | 90 | 90 | **100%** ✅ |
| JavaScript | 90 | 90 | **100%** ✅ |
| C# | 90 | 90 | **100%** ✅ |
| C (新增) | 90 | 90 | **100%** ✅ |

**结论：所有生成器已实现全部 90 个 IR 接口方法。之前的"60% 覆盖率"是误判。**

### PHP 函数映射覆盖率

| 生成器 | 扩展前 | 扩展后 | 主要新增函数 |
|--------|--------|--------|----------|
| Swift | 22 | 55+ | trim/ltrim/rtrim, strtoupper/strtolower, ucfirst/lcfirst, str_replace, str_repeat, str_pad, md5/sha1, array_keys/values/merge/slice/reverse/sum/map/filter/search/column/flip/fill/rand/shift/pop/unshift/key_exists, abs/min/max/rand/sqrt/log/sin/cos/tan, is_int/float/string/bool/numeric, time/date, urlencode/urldecode, base64_encode/decode, sprintf |
| Kotlin | 21 | 55+ | 同上 + Kotlin 特有实现 |
| Dart | 21 | 55+ | 同上 + Dart 特有实现 |
| JavaScript | 21 | 55+ | 同上 + JavaScript 特有实现 |
| C# | 21 | 55+ | 同上 + C# 特有实现 |
| C | 0 | 0 | 直接透传（C 代码生成） |

### 扩展后 PHP 函数映射分类

| 类别 | 函数 |
|------|------|
| 字符串操作 | trim/ltrim/rtrim, strtoupper/strtolower, ucfirst/lcfirst, str_replace, str_repeat, str_pad, strip_tags, htmlspecialchars, md5, sha1 |
| 字符串操作 | substr, strlen, strpos, substr_count |
| 数组操作 | in_array, empty, count, array_push, array_keys, array_values, array_merge, array_slice, array_reverse, array_sum, array_map, array_filter, array_search, array_column, array_flip, array_fill, array_rand, array_shift, array_pop, array_unshift, array_key_exists |
| 数学函数 | floor, ceil, round, abs, min, max, rand, sqrt, log, sin, cos, tan |
| 类型检查 | is_null, is_array, is_int, is_float, is_string, is_bool, is_numeric |
| 日期时间 | time, date |
| 编码 | urlencode, urldecode, base64_encode, base64_decode |
| 格式化 | number_format, sprintf, json_decode, json_encode |
| 类型转换 | floatval/doubleval, intval/int, strval |

### 测试覆盖率

| 测试文件 | 测试数 | 覆盖范围 |
|----------|--------|----------|
| SwiftGeneratorTest | 19 | 基础类型 + 函数映射 |
| KotlinGeneratorTest | 22 | 基础类型 + 函数映射 + state |
| DartGeneratorTest | 22 | 基础类型 + 函数映射 + state |
| JavaScriptGeneratorTest | 43 | 基础类型 + 函数映射 + state + IR 节点（扩展后） |
| CSharpGeneratorTest | 22 | 基础类型 + 函数映射 + state |
| CGeneratorTest | 46 | 所有 IR 节点（新增） |
| IRNodesTest | 31 | 所有 IR 节点在所有生成器中的行为 |
| **总计** | **205** | **IR 生成器测试** |

### 真实差距（已修复）

1. ~~**JavaScript 生成器测试最少**（14 vs 22）~~ ✅ 已扩展至 43 个测试
2. ~~**CGenerator 没有测试**~~ ✅ 已创建 46 个测试
3. ~~**PHP 函数映射可扩展**~~ ✅ 已从 21-22 个扩展至 50+ 个
4. **IR 节点方法无测试** — 90 个 IR 方法中，大部分没有独立测试（IRNodesTest 只覆盖部分）

### 优先级排序（更新）

| 优先级 | 任务 | 影响 | 状态 |
|--------|------|------|------|
| P0 | 添加 CGenerator 测试 | 新增功能，无测试保护 | ✅ COMPLETE |
| P1 | 补充 JavaScript 生成器测试 | 覆盖率最低 | ✅ COMPLETE |
| P2 | 扩展 PHP 函数映射 | 提升 Closure 转译能力 | ✅ COMPLETE |
| P3 | IR 节点方法独立测试 | 提高测试粒度 | ⏭️ 可选 |
