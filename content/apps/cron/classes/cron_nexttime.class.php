<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

class cron_nexttime
{
    protected $day;
    protected $week;
    protected $hour;
    protected $minute;
    
    /**
     * 年 1999
     * @var string
     */
    private $now_year;
    
    /**
     * 月 01-12
     * @var string
     */
    private $now_month;
    
    /**
     * 日 01-31
     * @var string
     */
    private $now_day;
    
    /**
     * 周 0-6
     * @var string
     */
    private $now_week;
    
    /**
     * 小时 00-23
     * @var string
     */
    private $now_hour;
    
    /**
     * 分钟 00-59
     * @var string
     */
    private $now_minute;
    
    /**
     * 秒 00-59
     * @var string
     */
    private $now_second;
    
    /**
     * $cron数组元素
     * day 
     * week
     * hour
     * m
     * @param array $cron
     */
    public function __construct(array $cron)
    {
        $this->day    = array_get($cron, 'day', 0);
        $this->week   = array_get($cron, 'week', '');
        $this->hour   = array_get($cron, 'hour', 0);
        $this->minute = array_get($cron, 'm');
        
        $timestamp = RC_Time::gmtime();
        
        $this->now_year   = RC_Time::local_date('Y', $timestamp); // 年 1999
        $this->now_month  = RC_Time::local_date('n', $timestamp); // 月 01-12
        $this->now_day    = RC_Time::local_date('j', $timestamp); // 日 01-31
        $this->now_week   = RC_Time::local_date('w', $timestamp); // 周 0-6
        $this->now_hour   = RC_Time::local_date('G', $timestamp); // 小时 00-23
        $this->now_minute = RC_Time::local_date('i', $timestamp); // 分钟 00-59
        $this->now_second = RC_Time::local_date('s', $timestamp); // 秒 00-59
    }
    
    
    public function everyMonth() 
    {
        $syear 	= $this->now_year;
        $smonth = $this->now_month;
        $sday 	= $this->day;
        $shour 	= $this->hour;
        list($minutes_original, $minutes) = $this->parseMinute();
        
        if($sday < $this->now_day){
        	$smonth = $this->now_month+1;
        }
 
        if ($this->now_day ==$sday && count($minutes) > 0 && $this->now_hour == $this->hour) {
        	$sminute = reset($minutes);
        	$sday = $this->day;
        } else {
        	$sminute = reset($minutes_original);
        	
        	if($this->now_day ==$sday && $shour < $this->now_hour) {
        		$smonth = $this->now_month+1;
        	}
        	
        	if($this->now_day ==$sday && $shour == $this->now_hour && $sminute < $this->now_minute) {
        		$smonth = $this->now_month+1;
        	}
        }

        $ssecond = 0;
        
        return $this->makeDateTime($syear, $smonth, $sday, $shour, $sminute, $ssecond);
    }
    
    
    public function everyWeek()
    {
        $syear  = $this->now_year;
        $smonth = $this->now_month;
        $sday   = $this->now_day + $this->week - $this->now_week + 7;
        $shour  = $this->hour;
        
        list($minutes_original, $minutes) = $this->parseMinute();

        if (count($minutes) > 0 && $this->now_hour == $this->hour) {
            $sminute = reset($minutes);
            
            $sday = $this->now_day;
            
        } else {
            $sminute = reset($minutes_original);
            
            $sday = $this->now_day;
        }
        
        $week_day = $this->week - $this->now_week + 7;
        
        if ($this->week > $this->now_week ) {
            $sday += $this->week - $this->now_week;
            $sminute = reset($minutes_original);
        } 
        elseif ($this->week == $this->now_week) {
            if ($this->hour > $this->now_hour) {
                $sday += $this->week - $this->now_week;
            }
            elseif ($this->hour == $this->now_hour) {
                if ($sminute > $this->now_minute) {
                    
                } 
                else if ($sminute == $this->now_minute) {
                    $sminute = reset($minutes);
                }
                else {
                    $sday += $week_day;
                } 
            }
            else {
                $sday += $week_day;
            }
            
            
        } else {
            $sminute = reset($minutes_original);
            $sday += $week_day;
        }
        
        
        $ssecond = 0;
        
        return $this->makeDateTime($syear, $smonth, $sday, $shour, $sminute, $ssecond);
    }
    
    
    public function everyDay()
    {
        $syear  = $this->now_year;
        $smonth = $this->now_month;
        $sday   = $this->now_day;
        $shour  = $this->hour;
        
        list($minutes_original, $minutes) = $this->parseMinute();
        
        if ($shour < $this->now_hour) {
        	$sday++;
        }
        if (count($minutes) > 0 && $this->now_hour == $this->hour) {
        	$sminute = reset($minutes);
        } else {
            $sminute = reset($minutes_original);
            if($shour == $this->now_hour && $sminute < $this->now_minute) {
            	$sday++;
            }
        }
        
        $ssecond = 0;
        
        return $this->makeDateTime($syear, $smonth, $sday, $shour, $sminute, $ssecond);
    }
    
    protected function parseMinute()
    {
        if ( ! $this->minute) {
            return array(array(0), array());
        }
        
        $marray = $marray_original = explode(',', $this->minute);
        foreach ($marray as $k => $v){
            if ($v <= $this->now_minute){
                unset($marray[$k]);
            }
        }
        
        sort($marray);
        sort($marray_original);
        
        return array($marray_original, $marray);
    }
    
    /**
     * 生成时间
     * 
     * @param string $year
     * @param string $month
     * @param string $day
     * @param string $hour
     * @param string $minute
     * @param string $second
     * @return string
     */
    protected function makeDateTime($year, $month, $day, $hour, $minute, $second)
    {
        $day = intval($day) < 10 ? '0'.$day : $day;
        
        $minute = intval($minute) < 10 ? '0'.$minute : $minute;
        $second = intval($second) < 10 ? '0'.$second : $second;
        
        $time = RC_Time::local_strtotime("$year-$month-$day $hour:$minute:$second");
        return $time;
    }
    
    public function getNextTime()
    {
        if ($this->day && $this->week === '') 
        {
            $nexttime = $this->everyMonth();
        }
        else if ($this->week === '' && empty($this->day))
        {
        	$nexttime = $this->everyDay();
        }  
        else if ($this->week !== '' && empty($this->day)) 
        {
            $nexttime = $this->everyWeek();
        }
        
        return $nexttime;
    }
    
    
    public static function make($cron)
    {
        $instance = new static($cron);
        return $instance;
    }
}

// end