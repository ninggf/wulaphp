<?php

namespace wulaphp\util;
/**
 * 农历类
 *
 * @author Leo Ning
 */
final class LunarCalendar {
    private $monCyl, $dayCyl, $yearCyl;
    private                   $year, $month, $day;
    private                   $isLeap;
    private static            $lunarInfo = [
        0x04bd8,
        0x04ae0,
        0x0a570,
        0x054d5,
        0x0d260,
        0x0d950,
        0x16554,
        0x056a0,
        0x09ad0,
        0x055d2,
        0x04ae0,
        0x0a5b6,
        0x0a4d0,
        0x0d250,
        0x1d255,
        0x0b540,
        0x0d6a0,
        0x0ada2,
        0x095b0,
        0x14977,
        0x04970,
        0x0a4b0,
        0x0b4b5,
        0x06a50,
        0x06d40,
        0x1ab54,
        0x02b60,
        0x09570,
        0x052f2,
        0x04970,
        0x06566,
        0x0d4a0,
        0x0ea50,
        0x06e95,
        0x05ad0,
        0x02b60,
        0x186e3,
        0x092e0,
        0x1c8d7,
        0x0c950,
        0x0d4a0,
        0x1d8a6,
        0x0b550,
        0x056a0,
        0x1a5b4,
        0x025d0,
        0x092d0,
        0x0d2b2,
        0x0a950,
        0x0b557,
        0x06ca0,
        0x0b550,
        0x15355,
        0x04da0,
        0x0a5d0,
        0x14573,
        0x052d0,
        0x0a9a8,
        0x0e950,
        0x06aa0,
        0x0aea6,
        0x0ab50,
        0x04b60,
        0x0aae4,
        0x0a570,
        0x05260,
        0x0f263,
        0x0d950,
        0x05b57,
        0x056a0,
        0x096d0,
        0x04dd5,
        0x04ad0,
        0x0a4d0,
        0x0d4d4,
        0x0d250,
        0x0d558,
        0x0b540,
        0x0b5a0,
        0x195a6,
        0x095b0,
        0x049b0,
        0x0a974,
        0x0a4b0,
        0x0b27a,
        0x06a50,
        0x06d40,
        0x0af46,
        0x0ab60,
        0x09570,
        0x04af5,
        0x04970,
        0x064b0,
        0x074a3,
        0x0ea50,
        0x06b58,
        0x055c0,
        0x0ab60,
        0x096d5,
        0x092e0,
        0x0c960,
        0x0d954,
        0x0d4a0,
        0x0da50,
        0x07552,
        0x056a0,
        0x0abb7,
        0x025d0,
        0x092d0,
        0x0cab5,
        0x0a950,
        0x0b4a0,
        0x0baa4,
        0x0ad50,
        0x055d9,
        0x04ba0,
        0x0a5b0,
        0x15176,
        0x052b0,
        0x0a930,
        0x07954,
        0x06aa0,
        0x0ad50,
        0x05b52,
        0x04b60,
        0x0a6e6,
        0x0a4e0,
        0x0d260,
        0x0ea65,
        0x0d530,
        0x05aa0,
        0x076a3,
        0x096d0,
        0x04bd7,
        0x04ad0,
        0x0a4d0,
        0x1d0b6,
        0x0d250,
        0x0d520,
        0x0dd45,
        0x0b5a0,
        0x056d0,
        0x055b2,
        0x049b0,
        0x0a577,
        0x0a4b0,
        0x0aa50,
        0x1b255,
        0x06d20,
        0x0ada0
    ];
    private static            $Gan       = ["甲", "乙", "丙", "丁", "戊", "己", "庚", "辛", "壬", "癸"];
    private static            $Zhi       = ["子", "丑", "寅", "卯", "辰", "巳", "午", "未", "申", "酉", "戌", "亥"];
    private static            $Animals   = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];
    private static            $nStr1     = ["日", "一", "二", "三", "四", "五", "六", "七", "八", "九", "十"];
    private static            $nStr2     = ["初", "十", "廿", "卅", "　"];
    private static            $monthNong = ["正", "正", "二", "三", "四", "五", "六", "七", "八", "九", "十", "十一", "腊"];
    private static            $yearName  = ["零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖"];

    // 传回农历 y年的总天数
    private function lYearDays($y) {
        $sum = 348; // 29*12
        for ($i = 0x8000; $i > 0x8; $i >>= 1) {
            $sum += (self::$lunarInfo [ $y - 1900 ] & $i) == 0 ? 0 : 1; // 大月+1天
        }

        return $sum + $this->leapDays($y); // +闰月的天数
    }

    // 传回农历 y年闰月的天数
    private function leapDays($y) {
        if ($this->leapMonth($y) != 0)
            return ((self::$lunarInfo [ $y - 1900 ] & 0x10000) == 0 ? 29 : 30); else
            return (0);
    }

    // 传回农历 y年闰哪个月 1-12 , 没闰传回 0
    private function leapMonth($y) {
        return (self::$lunarInfo [ $y - 1900 ] & 0xf);
    }

    // 传回农历 y年m月的总天数
    private function monthDays($y, $m) {
        $y_ = self::$lunarInfo [ $y - 1900 ];

        $x_ = 0x10000 >> $m;

        $x = $y_ & $x_;

        return $x == 0 ? 29 : 30;
    }

    // 算出农历, 传入日期物件, 传回农历日期物件
    private function _lunar($objDate) {
        $temp = 0;
        // 1900-01-31是农历1900年正月初一
        $baseDate     = - 2206425951469;
        $offset       = intval(($objDate - $baseDate) / 86400000); // 天数(86400000=24*60*60*1000)
        $this->dayCyl = $offset + 40; // 1899-12-21是农历1899年腊月甲子日
        $this->monCyl = 14; // 1898-10-01是农历甲子月
        // 得到年数
        for ($i = 1900; $i < 2050 && $offset > 0; $i ++) {
            $temp         = $this->lYearDays($i); // 农历每年天数
            $offset       -= $temp;
            $this->monCyl += 12;
        }
        if ($offset < 0) {
            $offset += $temp;
            $i --;
            $this->monCyl -= 12;
        }
        $this->year    = $i; // 农历年份
        $this->yearCyl = $i - 1864; // 1864年是甲子年
        $leap          = $this->leapMonth($i); // 闰哪个月
        $this->isLeap  = false;
        for ($i = 1; $i < 13 && $offset > 0; $i ++) {
            // 闰月
            if ($leap > 0 && $i == ($leap + 1) && $this->isLeap == false) {
                -- $i;
                $this->isLeap = true;
                $temp         = $this->leapDays($this->year);
            } else {
                $temp = $this->monthDays($this->year, $i);
            }
            // 解除闰月
            if ($this->isLeap == true && $i == ($leap + 1))
                $this->isLeap = false;
            $offset -= $temp;
            if ($this->isLeap == false)
                $this->monCyl ++;
        }
        if ($offset == 0 && $leap > 0 && $i == $leap + 1)
            if ($this->isLeap) {
                $this->isLeap = false;
            } else {
                $this->isLeap = true;
                -- $i;
                -- $this->monCyl;
            }
        if ($offset < 0) {
            $offset += $temp;
            -- $i;
            -- $this->monCyl;
        }
        $this->month = $i; // 农历月份
        $this->day   = $offset + 1; // 农历天份
    }

    // 传入 offset 传回干支, 0=甲子
    private static function cyclical($num) {
        return (self::$Gan [ $num % 10 ] . self::$Zhi [ $num % 12 ]);
    }

    // 中文日期
    private static function cDay($d) {
        $s = '';
        switch ($d) {
            case 10 :
                $s = "初十";
                break;
            case 20 :
                $s = "二十";
                break;
            case 30 :
                $s = "三十";
                break;
            default :
                $s = self::$nStr2 [ intval($d / 10) ]; // 取商
                $s .= self::$nStr1 [ $d % 10 ]; // 取余
        }

        return $s;
    }

    private static function cYear($y) {
        $s = '';
        while ($y > 0) {
            $d = $y % 10;
            $y = ($y - $d) / 10;
            $s = self::$yearName [ $d ] . $s;
        }

        return $s;
    }

    /**
     * 将阳历换算成中国的阴历
     *
     * @param string $date
     *
     * @return array
     */
    public function lunar(string $date = '') {
        if (empty ($date)) {
            $date = date('Y-m-d');
        }
        $sDObj = strtotime($date . ' 00:00:00') * 1000;
        // 计算农历
        $this->_lunar($sDObj);
        $sy = ($this->year - 4) % 12;

        $lunar             = [];
        $lunar ['year']    = $this->year; // 数字表示的年
        $lunar ['month']   = $this->month; // 数字表示的月
        $lunar ['day']     = $this->day; // 数字表示的天
        $lunar ['lYear']   = self::cYear($this->year); // 汉字表示的年
        $lunar ['lMonth']  = self::$monthNong [ $this->month ]; // 汉字表示的月
        $lunar ['lDay']    = self::cDay($this->day); // 汉字表示的天
        $lunar ['cYear']   = self::cyclical($this->yearCyl); // 干支表示的年
        $lunar ['cMonth']  = self::cyclical($this->monCyl); // 干支表示的月
        $lunar ['cDay']    = self::cyclical($this->dayCyl); // 干支表示的天
        $lunar ['animals'] = self::$Animals [ $sy ]; // 属相
        $lunar ['leap']    = $this->isLeap; // 是否是闰月
        $lunar ['bigMon']  = self::monthDays(intval($this->year), intval($this->month)) == 30; // 是否是大月

        return $lunar;
    }
}