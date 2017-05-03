<?php
  
defined('IN_ECJIA') or exit('No permission resources.');

use Royalcms\Component\Database\Connection;

RC_Package::package('app::installer')->loadClass('sql_parse', false);

class sql_query
{
    /**
     * 数据库连接对象
     *
     * @var     \Royalcms\Component\Database\Connection      $connection
     */
    private $connection;
    
    
    /**
     * 数据库字符编码
     *
     * @var      string     $charset
     */
    private $dbCharset;
    
    
    /**
     * 替换前表前缀
     *
     * @var     string      $sourcePrefix
     */
    private $sourcePrefix;
    
    
    /**
     * 替换后表前缀
     *
     * @var     string      $targetPrefix
     */
    private $targetPrefix;
    
    
    /**
     * 记录当前正在执行的SQL文件名
     *
     * @var     string       $currentFile
     */
    private $currentFile = 'Not a file, but a string.';
    
    /**
     * 开启此选项后，程序将进行智能化地查询操作，即使重复运行本程序，也不会引起数据库的查询冲突。这点在浏览器
     * 和服务器之间进行通讯时是非常有必要的，因为网络很有可能在您不经意间发生中断。不过，由于用到了大量的正则
     * 表达式，开启该选项后将非常耗费服务器的资源。
     *
     * @var     boolean      $autoMatch
     */
    private $autoMatch = false;
    
    
    /**
     * 记录程序执行过程中最后产生的那条错误信息
     *
     * @var     ecjia_error       $error
     */
    public $error;
    
    
    /**
     * 构造函数
     *
     * @param   mysql       $connection     mysql连接对象
     * @param   string      $charset        字符集
     * @param   string      $sprefix        替换前表前缀
     * @param   string      $tprefix        替换后表前缀
     * @return  void
     */
    public function __construct(Connection $connection, $charset = 'utf8', $sprefix = 'ecjia_', $tprefix = 'ecjia_')
    {
        $this->connection = $connection;
        $this->dbCharset = $charset;
        $this->sourcePrefix = $sprefix;
        $this->targetPrefix = $tprefix;
        
        $this->error = new ecjia_error();
        
        $this->sqlExecutor("SET NAMES '$this->dbCharset'");
    }
    
    /**
     * 执行所有SQL文件中所有的SQL语句
     *
     * @param   array       $sqlFiles     文件绝对路径组成的一维数组
     * @return  boolean     执行成功返回true，失败返回false。
     */
    public function runAll($sqlFiles)
    {
        /* 如果传入参数不是数组，程序直接返回 */
        if (!is_array($sqlFiles))
        {
            return false;
        }
        
        foreach ($sqlFiles AS $file)
        {
            $query_items = $this->parseSqlFile($file);
        
            /* 如果解析失败，则跳过 */
            if (!$query_items)
            {
                continue;
            }
        
            foreach ($query_items AS $query_item)
            {
                /* 如果查询项为空，则跳过 */
                if (!$query_item)
                {
                    continue;
                }
        
                if (!$this->query($query_item))
                {
                    return false;
                }
            }
        }
        
        return true;
        
    }
    
    /**
     * 获得分散的查询项
     *
     * @param   string      $filePath      文件的绝对路径
     * @return  mixed       解析成功返回分散的查询项数组，失败返回false。
     */
    public function parseSqlFile($filePath)
    {
        /* 如果SQL文件不存在则返回false */
        if (!file_exists($filePath))
        {
            return false;
        }
    
        /* 记录当前正在运行的SQL文件 */
        $this->currentFile = $filePath;
    
        /* 读取SQL文件 */
        $sql = implode('', file($filePath));
    
        /* 删除SQL注释，由于执行的是replace操作，所以不需要进行检测。下同。 */
        $sql = $this->removeComment($sql);
    
        /* 删除SQL串首尾的空白符 */
        $sql = trim($sql);
    
        /* 如果SQL文件中没有查询语句则返回false */
        if (!$sql)
        {
            return false;
        }
    
        /* 替换表前缀 */
        $sql = $this->replacePrefix($sql);
        
        /* 解析查询项 */
        $sql = str_replace("\r", '', $sql);
        $queryItems = explode(";\n", $sql);
        
        $queryItems = array_map(function ($query) {
        	return trim($query);
        }, $queryItems);

        return $queryItems;
    }
    
    /**
     *   获得SQL文件中指定的查询项
     *
     * @param   string    $filePath       SQL查询项
     * @param   int       $pos             查询项的索引号
     * @return  mixed     成功返回该查询项，失败返回false。
     */
    public function getSpecQueryItem($filePath, $pos)
    {
        $queryItems = $this->parseSqlFile($filePath);
    
        if (empty($queryItems) || empty($queryItems[$pos]))
        {
            return false;
        }
    
        return $queryItems[$pos];
    }
    
    
    /**
     * 执行某一个查询项
     *
     * @param   string      $query_item      查询项
     * @return  boolean     成功返回true，失败返回false。
     */
    public function query($queryItem)
    {
        /* 删除查询项首尾的空白符 */
        $queryItem = trim($queryItem);
    
        /* 如果查询项为空则返回false */
        if (!$queryItem)
        {
            return false;
        }
        
        /* 处理建表操作 */
        if (preg_match('/^\s*CREATE\s+TABLE\s*/i', $queryItem))
        {
            if (!$this->createTable($queryItem))
            {
                return false;
            }
        }
        /* 处理ALTER TABLE语句，此时程序将对表的结构进行修改 */
        elseif ($this->autoMatch && preg_match('/^\s*ALTER\s+TABLE\s*/i', $queryItem))
        {
            if (!$this->alterTable($queryItem))
            {
                return false;
            }
        }
        /* 处理其它修改操作，如数据添加、更新、删除等 */
        else
        {
            if (!$this->doOtherSql($queryItem))
            {
                return false;
            }
        }
    
        return true;
    }
    
    
    /**
     * 过滤SQL查询串中的注释。该方法只过滤SQL文件中独占一行或一块的那些注释。
     *
     * @param   string      $sql        SQL查询串
     * @return  string      返回已过滤掉注释的SQL查询串。
     */
    public function removeComment($sql)
    {
        /* 删除SQL行注释，行注释不匹配换行符 */
        $sql = preg_replace('/^\s*(?:--|#).*/m', '', $sql);
    
        /* 删除SQL块注释，匹配换行符，且为非贪婪匹配 */
        //$sql = preg_replace('/^\s*\/\*(?:.|\n)*\*\//m', '', $sql);
        $sql = preg_replace('/^\s*\/\*.*?\*\//ms', '', $sql);
    
        return $sql;
    }
    
    
    /**
     * 替换查询串中数据表的前缀。该方法只对下列查询有效：CREATE TABLE,
     * DROP TABLE, ALTER TABLE, UPDATE, REPLACE INTO, INSERT INTO
     *
     * @access  public
     * @param   string      $sql        SQL查询串
     * @return  string      返回已替换掉前缀的SQL查询串。
     */
    public function replacePrefix($sql)
    {
        $keywords = 'CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|'
                  . 'DROP\s+TABLE(?:\s+IF\s+EXISTS)?|'
                  . 'ALTER\s+TABLE|'
                  . 'UPDATE|'
                  . 'REPLACE\s+INTO|'
                  . 'DELETE\s+FROM|'
                  . 'INSERT\s+INTO';
    
        $pattern = '/(' . $keywords . ')(\s*)`?' . $this->sourcePrefix . '(\w+)`?(\s*)/i';
        $replacement = '\1\2`' . $this->targetPrefix . '\3`\4';
        $sql = preg_replace($pattern, $replacement, $sql);
    
        $pattern = '/(UPDATE.*?WHERE)(\s*)`?' . $this->sourcePrefix . '(\w+)`?(\s*\.)/i';
        $replacement = '\1\2`' . $this->targetPrefix . '\3`\4';
        $sql = preg_replace($pattern, $replacement, $sql);
    
        return $sql;
    }
    
    
    /**
     * 概据MYSQL版本，创建数据表
     *
     * @access  public
     * @param   string      $query_item     SQL查询项
     * @return  boolean     成功返回true，失败返回false。
     */
    public function createTable($queryItem)
    {
        /* 获取建表主体串以及表属性声明串，不区分大小写，匹配换行符，且为贪婪匹配 */
        $pattern = '/^\s*(CREATE\s+TABLE[^(]+\(.*\))(.*)$/is';
        if (!preg_match($pattern, $queryItem, $matches))
        {
            return false;
        }
        $main = $matches[1];
        $postfix = $matches[2];
    
        /* 从表属性声明串中查找表的类型 */
        $pattern = '/.*(?:ENGINE|TYPE)\s*=\s*([a-z]+).*$/is';
        $type = preg_match($pattern, $postfix, $matches) ? $matches[1] : 'InnoDB';
    
        /* 从表属性声明串中查找自增语句 */
        $pattern = '/.*(AUTO_INCREMENT\s*=\s*\d+).*$/is';
        $auto_incr = preg_match($pattern, $postfix, $matches) ? $matches[1] : '';
    
        /* 重新设置表属性声明串 */
        $postfix = " ENGINE=$type DEFAULT CHARACTER SET " . $this->dbCharset;
        $postfix .= ' ' . $auto_incr;
    
        /* 重新构造建表语句 */
        $sql = $main . $postfix;

        /* 开始创建表 */
        if (!$this->sqlExecutor($sql))
        {
            return false;
        }

        return true;
    }
        
    
    /**
     * 修改数据表的方法。算法设计思路：
     * 1. 先进行字段修改操作。CHANGE
     * 2. 然后进行字段移除操作。DROP [COLUMN]
     * 3. 接着进行字段添加操作。ADD [COLUMN]
     * 4. 进行索引移除操作。DROP INDEX
     * 5. 进行索引添加操作。ADD INDEX
     * 6. 最后进行其它操作。
     *
     * @access  public
     * @param   string      $query_item     SQL查询项
     * @return  boolean     修改成功返回true，否则返回false
     */
    public function alterTable($queryItem)
    {
        /* 获取表名 */
        $tableName = $this->getTableName($queryItem, 'ALTER');
        if (!$tableName)
        {
            return false;
        }
        
        $fields = $this->getFields($tableName);
        $indexes = $this->getIndexes($tableName);
        
        $parse = new sql_parse($tableName, $this->dbCharset, $fields, $indexes);
    
        /* 先把CHANGE操作提取出来执行，再过滤掉它们 */
        $result = $parse->parseChangeQuery($queryItem);
        if ($result[0] && !$this->sqlExecutor($result[0]))
        {
            return false;
        }
        if (!$result[1])
        {
            return true;
        }
        
        /* 把DROP [COLUMN]提取出来执行，再过滤掉它们 */
        $result = $parse->parseDropColumnQuery($result[1]);
        if ($result[0] && !$this->sqlExecutor($result[0]))
        {
            return false;
        }
        if (!$result[1])
        {
            return true;
        }

        /* 把ADD [COLUMN]提取出来执行，再过滤掉它们 */
        $result = $parse->parseAddColumnQuery($result[1]);
        if ($result[0] && !$this->sqlExecutor($result[0]))
        {
            return false;
        }
        if (!$result[1])
        {
            return true;
        }

        /* 把DROP INDEX提取出来执行，再过滤掉它们 */
        $result = $this->parseDropIndexQuery($result[1]);
        if ($result[0] && !$this->sqlExecutor($result[0]))
        {
            return false;
        }
        if (!$result[1])
        {
            return true;
        }
    
        /* 把ADD INDEX提取出来执行，再过滤掉它们 */
        $result = $this->parseAddIndexQuery($result[1]);
        if ($result[0] && !$this->sqlExecutor($result[0]))
        {
            return false;
        }
        
        /* 执行其它的修改操作 */
        if ($result[1] && !$this->sqlExecutor($result[1]))
        {
            return false;
        }
    
        return true;
    }
    
    /**
     * 处理其它的数据库操作
     *
     * @param   string      $queryItem     SQL查询项
     * @return  boolean     成功返回true，失败返回false。
     */
    private function doOtherSql($queryItem)
    {
        if (!$this->sqlExecutor($queryItem))
        {
            return false;
        }
    
        return true;
    }
    
    private function sqlExecutor($sql)
    {
        try {
            
            $result = $this->connection->getPdo()->query($sql);
            return $result;
            
        } catch (PDOException $e) {
            
            $this->error->add($e->getCode(), $e->getMessage(), $sql);
            return false;
            
        }
    }
    
    /**
     * 获取表的名字。该方法只对下列查询有效：CREATE TABLE,
     * DROP TABLE, ALTER TABLE, UPDATE, REPLACE INTO, INSERT INTO
     *
     * @param   string      $query    SQL查询项
     * @param   string      $type     查询类型
     * @return  mixed       成功返回表的名字，失败返回false。
     */
    public function getTableName($query, $type = null)
    {
        $pattern = '';
        $matches = array();
        $tableName = '';
    
        /* 如果没指定$type，则自动获取 */
        if (!$type && preg_match('/^\s*(\w+)/', $query, $matches))
        {
            $type = $matches[1];
        }
    
        /* 获取相应的正则表达式 */
        $type = strtoupper($type);
        switch ($type)
        {
        	case 'ALTER' :
        	    $pattern = '/^\s*ALTER\s+TABLE\s*`?(\w+)/i';
        	    break;
        	case 'CREATE' :
        	    $pattern = '/^\s*CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s*`?(\w+)/i';
        	    break;
        	case 'DROP' :
        	    $pattern = '/^\s*DROP\s+TABLE(?:\s+IF\s+EXISTS)?\s*`?(\w+)/i';
        	    break;
        	case 'INSERT' :
        	    $pattern = '/^\s*INSERT\s+INTO\s*`?(\w+)/i';
        	    break;
        	case 'REPLACE' :
        	    $pattern = '/^\s*REPLACE\s+INTO\s*`?(\w+)/i';
        	    break;
        	case 'UPDATE' :
        	    $pattern = '/^\s*UPDATE\s*`?(\w+)/i';
        	    break;
        	default :
        	    return false;
        }
    
        if (!preg_match($pattern, $query, $matches))
        {
            return false;
        }
        $tableName = $matches[1];
    
        return $tableName;
    }
    
    /**
     * 获取所有的fields
     *
     * @param   string      $tableName      数据表名
     * @return  array
     */
    public function getFields($tableName)
    {
        $fields = array();
    
        $result = $this->connection->query("SHOW FIELDS FROM $tableName");
    
        if ($result)
        {
            $indexes = collect($result)->lists('Field');
        }
    
        return $fields;
    }
    
    /**
     * 获取所有的indexes
     *
     * @access  public
     * @param   string      $table_name      数据表名
     * @return  array
     */
    public function getIndexes($tableName)
    {
        $indexes = array();
    
        $result = $this->connection->select("SHOW INDEX FROM $tableName");
    
        if ($result)
        {
            $indexes = collect($result)->lists('Key_name');
        }
    
        return $indexes;
    }
    
    /**
     * 获取错误对象 ecjia_error
     */
    public function getError()
    {
        return $this->error;
    }
}

//end
