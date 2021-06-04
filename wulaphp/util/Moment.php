<?php

namespace wulaphp\util;

defined('CURRENT_DEFAULT_TZ') or define('CURRENT_DEFAULT_TZ', date_default_timezone_get());

/**
 * 此时此刻类.
 *
 * @property-read int $beginStamp     一天开始的timestamp
 * @property-read int $endStamp       一天最后一秒的timestamp
 * @property-read int $timestamp      timestamp
 * @property-read int $year
 * @property-read int $month
 * @property-read int $month1
 * @property-read int $day
 * @property-read int $day1
 * @property-read int $hour
 * @property-read int $hour1
 * @property-read int $minute
 * @property-read int $minute1
 * @property-read int $second
 * @property-read int $second1
 * @property-read int $dayOfWeek
 * @property-read int $dayOfYear
 * @property-read int $weekOfYear
 *
 * @author Leo Ning <windywany@gmail.com>
 * @since  3.9.9
 */
class Moment {
    private $timestamp;
    private $timezone;
    private $begin;
    private $end;
    private $dateInfo = [];
    private $oldTimezone;

    private function __construct(int $timestamp, ?string $timezone = null) {
        $this->timestamp = $timestamp ?: time();
        $this->timezone  = $timezone;
        $this->begin     = strtotime(date('Y-m-d 00:00:00', $this->timestamp));
        $this->end       = strtotime(date('Y-m-d 23:59:59', $this->timestamp));
    }

    /**
     * 当前时间。
     *
     * @return \wulaphp\util\Moment
     */
    public static function now(): Moment {
        return new self(time());
    }

    /**
     * 从日期字符串解析日期.
     *
     * @param string      $datetime
     * @param string|null $timezone
     *
     * @return \wulaphp\util\Moment
     * @throws \Exception
     */
    public static function parse(string $datetime, ?string $timezone = null): Moment {
        if ($timezone) {
            if (!@date_default_timezone_set($timezone)) {
                throw new \Exception('illegal timezone: ' . $timezone);
            }
        }

        try {
            return new self(strtotime($datetime), $timezone);
        } finally {
            $timezone && date_default_timezone_set(CURRENT_DEFAULT_TZ);
        }
    }

    /**
     * 从timestamp创建.
     *
     * @param int $timestamp
     *
     * @return \wulaphp\util\Moment
     */
    public static function from(int $timestamp): Moment {
        return new self($timestamp);
    }

    /**
     * GMT时间.
     *
     * @param int         $year
     * @param int         $month
     * @param int         $dayOfMonth
     * @param string|null $timezone
     *
     * @return \wulaphp\util\Moment
     * @throws \Exception
     */
    public static function of(int $year, int $month, int $dayOfMonth, ?string $timezone = null): Moment {
        if ($timezone) {
            if (!@date_default_timezone_set($timezone)) {
                throw new \Exception('illegal timezone: ' . $timezone);
            }
        }
        try {
            $time = mktime(0, 0, 0, $month, $dayOfMonth, $year);

            return new self($time, $timezone);
        } finally {
            $timezone && date_default_timezone_set(CURRENT_DEFAULT_TZ);
        }
    }

    /**
     * 指定时区.
     *
     * @param string $timezone
     *
     * @return \wulaphp\util\Moment
     */
    public function with(string $timezone): Moment {
        if (!$this->oldTimezone) {# 保留最原始的timezone，需要调用reset还原.
            $this->oldTimezone = $this->timezone;
        }

        $this->timezone = $timezone;

        return $this;
    }

    /**
     * 重置时区.
     * @return \wulaphp\util\Moment
     */
    public function reset(): Moment {
        $this->timezone    = $this->oldTimezone;
        $this->oldTimezone = null;

        return $this;
    }

    /**
     * 此刻所在月的第一天
     *
     * @param bool $withTime
     *
     * @return \wulaphp\util\Moment
     */
    public function firstDayOfMonth(): Moment {
        $str = date('Y-m-01', $this->timestamp);

        return new self(strtotime($str));
    }

    /**
     * 此刻所在月的最后一天.
     *
     * @param bool $withTime
     *
     * @return \wulaphp\util\Moment
     */
    public function lastDayOfMonth(): Moment {
        $str = date('Y-m-t', $this->timestamp);

        return new self(strtotime($str));
    }

    /**
     * 此刻所在周的周一
     * @return \wulaphp\util\Moment
     */
    public function firstDayOfWeek(): Moment {
        $dayOfWeek = $this->dayOfWeek;
        $delta     = ($dayOfWeek - 1) * 86400;

        return new self($this->timestamp - $delta);
    }

    /**
     * 此刻所在周的周天
     *
     * @return \wulaphp\util\Moment
     */
    public function lastDayOfWeek(): Moment {
        $dayOfWeek = $this->dayOfWeek;
        $delta     = (7 - $dayOfWeek) * 86400;

        return new self($this->timestamp + $delta);
    }

    /**
     * 此刻所在年的第一天
     *
     * @return \wulaphp\util\Moment
     */
    public function firstDayOfYear(): Moment {
        $str = date('Y-01-01', $this->timestamp);

        return new self(strtotime($str));
    }

    /**
     * 此刻所在年的最后一天
     *
     * @return \wulaphp\util\Moment
     */
    public function lastDayOfYear(): Moment {
        $str = date('Y-12-31', $this->timestamp);

        return new self(strtotime($str));
    }

    /**
     * 调整时间
     *
     *
     * @param string $modify 仅支持 strotime 参数格式
     *
     * @return \wulaphp\util\Moment
     */
    public function modify(string $modify): Moment {
        return new self(strtotime($modify, $this->timestamp), $this->timezone);
    }

    /**
     * 年调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addYears(int $delta): Moment {
        return $this->modify($delta . ' year');
    }

    /**
     * 月调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addMonths(int $delta): Moment {
        return $this->modify($delta . ' month');
    }

    /**
     * 周调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addWeeks(int $delta): Moment {
        return $this->modify($delta . ' week');
    }

    /**
     * 天调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addDays(int $delta): Moment {
        return $this->modify($delta . ' day');
    }

    /**
     * 小时调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addHours(int $delta): Moment {
        return $this->modify($delta . ' hour');
    }

    /**
     * 分调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addMinutes(int $delta): Moment {
        return $this->modify($delta . ' minute');
    }

    /**
     * 秒调整
     *
     * @param int $delta
     *
     * @return \wulaphp\util\Moment
     */
    public function addSeconds(int $delta): Moment {
        return $this->modify($delta . ' second');
    }

    /**
     * 日期时间
     *
     * @param string $format
     *
     * @return false|string
     */
    public function datetime(string $format = 'Y-m-d H:i:s'): string {
        return $this->format($format);
    }

    /**
     * 日期
     *
     * @param string $format
     *
     * @return false|string
     */
    public function day(string $format = 'Y-m-d'): string {
        return $this->format($format);
    }

    /**
     * 时间
     *
     * @param string $format
     *
     * @return false|string
     */
    public function time(string $format = 'H:i:s'): string {
        return $this->format($format);
    }

    /**
     * 一天开始时间.
     *
     * @param string $format
     *
     * @return false|string
     */
    public function begin(string $format = 'Y-m-d H:i:s'): string {
        return $this->format($format, $this->begin);
    }

    /**
     * 一天结束时间.
     *
     * @param string $format
     *
     * @return false|string
     */
    public function end(string $format = 'Y-m-d H:i:s'): string {
        return $this->format($format, $this->end);
    }

    /**
     * 格式化输出.
     *
     * @param string   $format
     * @param int|null $timestamp
     *
     * @return false|string
     */
    public function format(string $format = 'Y-m-d H:i:s', ?int $timestamp = null): string {
        try {
            $this->timezone && date_default_timezone_set($this->timezone);

            return date($format, $timestamp ?: $this->timestamp);
        } finally {
            $this->timezone && date_default_timezone_set(CURRENT_DEFAULT_TZ);
        }
    }

    /**
     * 计算两个时间的差
     *
     * @param \wulaphp\util\Moment $moment
     * @param bool|null            $ahead
     *
     * @return array
     */
    public function delta(Moment $moment, ?bool &$ahead = null): array {
        $myTimestamp = $this->timestamp;
        $itTimestamp = $moment->timestamp;
        $size        = $myTimestamp - $itTimestamp;
        $ahead       = $size >= 0;
        $delta       = [
            'day'    => 0,
            'hour'   => 0,
            'minute' => 0,
            'second' => 0
        ];
        $size        = abs($size);
        
        if ($size == 0) {
            return $delta;
        } else if ($size < 60) {
            $delta['second'] = $size;
        } else if ($size < 3600) {
            $delta['minute'] = floor($size / 60);
            $delta['second'] = fmod($size, 60);
        } else if ($size < 86400) {
            $delta['hour']   = floor($size / 3600);//取小时
            $delta['minute'] = floor(($size - $delta['hour'] * 3600) / 60);
            $delta['second'] = $size - $delta['hour'] * 3600 - $delta['minute'] * 60;
        } else {
            $delta['day']    = floor($size / 86400);//取天
            $remain          = $size - $delta['day'] * 86400;
            $delta['hour']   = floor($remain / 3600);//取小时
            $remain          = $remain - $delta['hour'] * 3600;
            $delta['minute'] = floor($remain / 60);
            $delta['second'] = $remain - $delta['minute'] * 60;
        }

        return $delta;
    }

    /**
     * @throws \Exception
     */
    public function __get(string $field): int {
        if ($field == 'beginStamp') {
            return $this->begin;
        } else if ($field == 'endStamp') {
            return $this->end;
        } else if ($field == 'timestamp') {
            return $this->timestamp;
        } else if (isset($this->dateInfo[ $field ])) {
            return $this->dateInfo[ $field ];
        }
        if ($this->timezone) {
            if (!@date_default_timezone_set($this->timezone)) {
                throw new \Exception('illegal timezone: ' . $this->timezone);
            }
        }
        // 0:年-1:[0]月-2:月-3:[0]天-4:天-5:[0]时-6:时-7:分-8:秒-9:周几-10:年天-11:年周
        $format  = 'Y-m-n-d-j-H-G-i-s-N-z-W';
        $dateStr = date($format, $this->timestamp);
        @date_default_timezone_set(CURRENT_DEFAULT_TZ);
        $date                         = explode('-', $dateStr);
        $this->dateInfo['year']       = $date[0];
        $this->dateInfo['month']      = $date[1];
        $this->dateInfo['month1']     = $date[2];
        $this->dateInfo['day']        = $date[3];
        $this->dateInfo['day1']       = $date[4];
        $this->dateInfo['hour']       = $date[5];
        $this->dateInfo['hour1']      = $date[6];
        $this->dateInfo['minute']     = $date[7];
        $this->dateInfo['minute1']    = ltrim($date[7], '0');
        $this->dateInfo['second']     = $date[8];
        $this->dateInfo['second1']    = ltrim($date[8], '0');
        $this->dateInfo['dayOfWeek']  = $date[9];
        $this->dateInfo['dayOfYear']  = $date[10];
        $this->dateInfo['weekOfYear'] = $date[11];
        if (!isset($this->dateInfo[ $field ])) {
            $this->dateInfo[ $field ] = - 1;
        }

        return $this->dateInfo[ $field ];
    }

    public function __toString(): string {
        return date('c', $this->timestamp);
    }
}
