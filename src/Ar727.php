<?php
namespace Oommgg\Soyal;

use Carbon\Carbon;

class Ar727
{
    const ACK = 4;
    const NACK = 5;

    /**
     * host
     *
     * @var string
     */
    protected $host;

    /**
     * port
     *
     * @var integer
     */
    protected $port;

    /**
     * target node id
     *
     * @var integer
     */
    protected $nodeId;

    /**
     * fsock resource
     *
     * @var resource
     */
    protected $fp;

    /**
     * construct
     *
     * @param string $host
     * @param integer $port
     * @param integer $nodeId
     */
    public function __construct(string $host, int $port = 1621, int $nodeId = 0x01)
    {
        $this->host = $host;
        $this->port = $port;
        $this->nodeId = $nodeId;
        $this->connect();
    }

    /**
     * connect socket
     *
     * @param integer $timeout
     * @return self
     */
    public function connect($timeout = 5): self
    {
        $this->fp = fsockopen($this->host, $this->port, $errno, $errstr, $timeout);
        if (!$this->fp) {
            throw new \Exception("$errstr ($errno)", $errno);
        }

        return $this;
    }

    /**
     * disconnect socket
     *
     * @return void
     */
    public function disconnect(): void
    {
        fclose($this->fp);
    }

    /**
     * receive data from socket
     *
     * @param boolean $terminate
     * @return array
     */
    protected function receive(): array
    {
        // ini_set("auto_detect_line_endings", true);
        $buffer = fread($this->fp, 65535);
        $unpack = unpack('C*', $buffer, 0);
        return $unpack;
    }

    /**
     * 取得卡機狀態
     *
     * @return array
     */
    public function getStatus(): array
    {
        $packed = pack('C*', ...$this->newExtPack(0x18));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        return $result;
    }

    /**
     * get card info by card number
     *
     * @param integer $address
     * @return array
     */
    public function getCard(int $address): array
    {
        $_address = unpack('C*', pack('S', $address), 0);
        $packed = pack('C*', ...$this->newExtPack(0x87, [$_address[2], $_address[1], 0x01]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        $uid1 = $this->parseUid($result[14], $result[15]);
        $uid2 = $this->parseUid($result[16], $result[17]);
        $status = $result[22] > 0 ? true : false;
        $time = Carbon::create(2000 + ($result[26] % 100), $result[27] % 100, $result[28] % 100);
        $expired = $time->isValid() ? $time->toDateString() : null;


        return [
            'address' => $address,
            'uid1' => $uid1,
            'uid2' => $uid2,
            'status' => $status,
            'expired' => $expired,
        ];

        // $result array
        //   14 => 183, //uid1 high: 0xB7
        //   15 => 17, //uid1 low: 0x11, 0xB711 => 46865
        //   16 => 251, //uid2 high: 0xFB
        //   17 => 62, //uid2 low: 0x3E, 0xFB3E => 64318
        //   22 => 64, //64: card only, 0: disabled
        //   26 => 99, //expire year
        //   27 => 12, //expire month
        //   28 => 31, //expire day
    }

    /**
     * set card info
     *
     * @param integer $address
     * @param integer $uid1
     * @param integer $uid2
     * @param boolean $disable
     * @return self
     */
    public function setCard(int $address, int $uid1, int $uid2, $disable = false): self
    {
        $_address = unpack('C*', pack('S', $address), 0);
        $tag1 = unpack('C*', pack('S', $uid1), 0);
        $tag2 = unpack('C*', pack('S', $uid2), 0);
        $status = $disable ? 0 : 64;
        $packed = pack('C*', ...$this->newExtPack(0x84, [
            1, //record number to set
            $_address[2], //user address HIGH bit
            $_address[1],  //user address LOW bit
            0,
            0,
            0,
            0,
            $tag1[2], //uid1 HIGH bit
            $tag1[1],  //uid1 LOW bit
            $tag2[2],  //uid2 high bit
            $tag2[1],  //uid2 low bit
            0, //pin
            0, //pin
            0, //pin
            0, //pin
            $status, //mode 0 for disable, 64 for enable
            0, //zone
            0xFF, //group1
            0xFF, //group2
            99, //year
            12, //month
            31, //day
            0, //level
            0,
            0,
            0,
            0
        ]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        if ($this->check($result) != self::ACK) {
            throw new \Exception("Error on setting card");
        }

        return $this;
    }

    /**
     * disable card
     *
     * @param integer $address
     * @return self
     */
    public function disableCard(int $address): self
    {
        $this->setCard($address, 65535, 65535, true);
        return $this;
    }

    /**
     * reset cards
     *
     * @param integer $start
     * @param integer $end
     * @return array
     */
    public function resetCards(int $start = 0, int $end = null): array
    {
        $tag1 = unpack('C*', pack('S', $start), 0);
        $tag2 = unpack('C*', pack('S', $end == null ? $start + 1 : $end), 0);
        $packed = pack('C*', ...$this->newExtPack(0x84, [$tag1[2], $tag1[1], $tag2[2], $tag2[1]]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        return $result;
    }

    /**
     * 取得卡機時間
     *
     * @return string
     */
    public function getTime(): string
    {
        $packed = pack('C*', ...$this->newExtPack(0x24));
        // fwrite($this->fp, $packed, strlen($packed));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        $time = Carbon::create(2000+$result[16], $result[15], $result[14], $result[12], $result[11], $result[10]);
        return $time->toDateTimeString();
    }

    /**
     * 設定卡機時間
     *
     * @param string $time
     * @return self
     */
    public function setTime(string $time = ''): self
    {
        $now = $time ? Carbon::parse($time, 'Asia/Taipei') : Carbon::now('Asia/Taipei');
        $packed = pack('C*', ...$this->newExtPack(0x23, [
            $now->second, $now->minute, $now->hour, $now->dayOfWeek + 1, $now->day, $now->month, $now->year % 100,
        ]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        if ($this->check($result) != self::ACK) {
            throw new \Exception("Error on setting time");
        }

        return $this;
    }

    /**
     * 取得卡機記憶體中最舊的 event log
     *
     * @return array
     */
    public function getOldestLog(): array
    {
        $packed = pack('C*', ...$this->newExtPack(0x25));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        $code = $this->check($result);

        // 沒有任何記錄了
        if ($code == self::ACK) {
            return [];
        }

        $time = Carbon::create(2000+$result[16], $result[15], $result[14], $result[12], $result[11], $result[10]);
        $funcCode = $this->getFunctionCode($result);
        $address = $this->parseUid($result[18], $result[19]);
        $uid1 = $this->parseUid($result[24], $result[25]);
        $uid2 = $this->parseUid($result[28], $result[29]);

        return [
            'time' => $time->toDateTimeString(),
            'func_code' => $funcCode,
            'address' => $address,
            'uid1' => $uid1,
            'uid2' => $uid2,
        ];
    }

    /**
     * 刪除卡機記憶體中最舊的 event log
     *
     * @return self
     */
    public function deleteOldestLog(): self
    {
        $packed = pack('C*', ...$this->newExtPack(0x37));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        if ($this->check($result) != self::ACK) {
            throw new \Exception('Error on deleting event log');
        }

        return $this;
    }

    /**
     * package data
     *
     * @param integer $command
     * @param array $data
     * @return array
     */
    protected function newPack(int $command, array $data = []): array
    {
        $length = 4;
        $xor = 0xFF ^ $this->nodeId ^ $command;
        $sum = $this->nodeId + $command;

        if ($data) {
            $length += count($data);
            foreach ($data as $d) {
                $xor ^= $d;
                $sum += $d;
            }
        }

        $sum += $xor;

        $buffer = [0x7E, $length, $this->nodeId, $command];

        if ($data) {
            array_push($buffer, ...$data);
        }

        array_push($buffer, $xor);
        array_push($buffer, $sum);

        return $buffer;
    }

    /**
     * extend packet data
     *
     * @param integer $command
     * @param array $data
     * @return array
     */
    protected function newExtPack(int $command, array $data = []): array
    {
        $length = 4;
        $xor = 0xFF ^ $this->nodeId ^ $command;
        $sum = $this->nodeId + $command;

        if ($data) {
            $length += count($data);
            foreach ($data as $d) {
                $xor ^= $d;
                $sum += $d;
            }
        }

        $xor = $xor % 256;
        $sum += $xor;
        $sum = $sum % 256;
        $length2 = unpack('C*', pack('L', $length), 0);
        $buffer = [0xFF, 0x00, 0x5A, 0xA5, $length2[2], $length2[1], $this->nodeId, $command];

        if ($data) {
            array_push($buffer, ...$data);
        }

        array_push($buffer, $xor);
        array_push($buffer, $sum);

        return $buffer;
    }

    /**
     * checksum return data
     *
     * @param array $data
     * @return int
     */
    protected function check(array $data): int
    {
        $extended = $data[1] == 0xFF;
        $start = $extended ? 7 : 3;
        $code = $extended ? $data[8] : $data[4];

        $xor = 0xFF;
        $sum = 0;
        $length = count($data) - 2;

        for ($i = $start; $i < $length; $i++) {
            $d = $data[$i];
            $xor ^= $d;
            $sum += $d;
        }

        $sum += $xor;

        $valid = $data[$length+1] == $xor % 256 && $data[$length+2] == $sum % 256;
        if (!$valid) {
            return -1;
        }

        return $code;
    }

    /**
     * 轉換 uid
     *
     * @param integer $param1
     * @param integer $param2
     * @return string
     */
    protected function parseUid(int $param1, int $param2): string
    {
        return sprintf("%05d", base_convert(sprintf("%02x", $param1).sprintf("%02x", $param2), 16, 10));
    }

    /**
     * get byte from data
     *
     * @param array $data
     * @param integer $index
     * @return integer
     */
    protected function getDataByte(array $data, int $index): int
    {
        $extended = $data[1] == 0xFF;
        $start = $extended ? 9 : 5;
        return $data[$start + $index];
    }

    /**
     * get function code from data
     *
     * @param array $data
     * @return integer
     */
    protected function getFunctionCode(array $data): int
    {
        return $data[1] == 0xFF ? $data[8] : $data[4];
    }
}
