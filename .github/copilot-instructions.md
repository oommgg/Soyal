# Soyal AR-727H TCP/IP Communication Library

## 專案概述

這是一個與 Soyal AR-727H 門禁控制器進行 TCP/IP 通訊的 PHP 函式庫。核心功能包括讀卡機管理、卡片資料 CRUD、事件記錄讀取及裝置時間同步。

## 架構要點

### 核心類別: `Ar727`

- **職責**: 封裝所有與 AR-727H 裝置的通訊協定
- **連線管理**: 使用 PHP `fsockopen()` 建立持久化 socket 連線,預設 port 1621
- **協定格式**: 自定義二進位協定,包含 STX、node ID、command code、data、XOR checksum 和 SUM checksum

### 二進位協定規範

```php
// 封包結構 (newExtPack方法)
[0xFF, 0x00, 0x5A, 0xA5, length_low, length_high, nodeId, command, ...data, xor, sum]
```

**關鍵實作細節**:

- 使用 `pack('C*', ...)` 將整數陣列打包為二進位字串
- 使用 `unpack('C*', $buffer)` 解析回應
- 雙重校驗: XOR checksum (`$xor`) 和 SUM checksum (`$sum`)
- 回應碼: `ACK=4` 表示成功, `NACK=5` 表示失敗

### 資料轉換模式

- **UID 編碼**: 16-bit 整數分解為兩個 8-bit bytes (high/low)
  ```php
  $uid = unpack('C*', pack('S', $uidValue), 0); // [2] = high, [1] = low
  ```
- **UID 解碼**: 兩個 bytes 組合為 5 位數十進位字串
  ```php
  parseUid($high, $low) => sprintf("%05d", hexdec("$high$low"))
  ```
- **時間戳**: 以 2000 年為基準 + offset 年份

## 開發工作流程

### 測試與除錯

- 使用 `test.php` 進行手動測試,需連接實際裝置
- 無單元測試 - 此為硬體通訊函式庫,需實體設備
- 除錯時使用 `print_r($result)` 檢查原始 byte 陣列回應

### 新增指令步驟

1. 查閱 AR-727H 通訊協定手冊找出 command code (如 `0x87` = 讀卡, `0x84` = 寫卡)
2. 在 `Ar727` 類別新增公開方法
3. 使用 `newExtPack($commandCode, $dataArray)` 建立封包
4. 透過 `fwrite($this->fp, $packed)` 發送
5. 使用 `receive()` 接收並用 `checksum()` 驗證校驗和
6. 解析回應資料並回傳有意義的陣列或物件

### 錯誤處理模式

- 連線失敗 → 拋出 `DeviceTimeOutException`
- 協定錯誤/裝置回應異常 → 拋出 `DeviceErrorException`
- 檢查 ACK/NACK 並在 NACK 時拋出例外 (如 `setCard()`)

## 關鍵程式碼模式

### 卡片管理範例

```php
// 啟用卡片: address 是內部索引 (0-16383), uid1/uid2 是卡片實體 ID
$soyal->setCard($address, $uid1, $uid2);

// 停用卡片: 設 uid 為 65535 且 status=0
$soyal->disableCard($address);
```

### 事件記錄輪詢模式

```php
while ($log = $soyal->getOldestLog()) {
    // 處理記錄...
    $soyal->deleteOldestLog(); // 必須刪除才能讀取下一筆
}
```

## 相依套件

- **nesbot/carbon v3**: 用於所有時間操作 (裝置使用 `Asia/Taipei` 時區)
- PHP 8.1+

## 專案特定慣例

- 使用 PSR-4 autoloading (`Oommgg\Soyal` namespace)
- 方法註解混用繁體中文與英文
- 保護方法 (`protected`) 處理低階協定操作
- 公開方法提供業務層級的 API
- 回傳值通常為陣列,包含解析後的結構化資料

## 重要檔案

- [src/Ar727.php](../src/Ar727.php): 所有通訊邏輯的單體類別
- [test.php](../test.php): 手動測試範例腳本
- [composer.json](../composer.json): 套件定義與相依性
