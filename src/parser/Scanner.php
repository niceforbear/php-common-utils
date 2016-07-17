<?php
/**
 * @author nesfoubaer
 * @date 16/6/18 下午1:52
 */

namespace niceforbear\utils\parser;

/**
 * 要解析一条语句, 必须先将它拆分成一些单词和字符(令牌).
 * Scanner类通过正则来定义令牌, 提供了结果栈.
 */
class Scanner
{
    /** token type */
    const WORD = 1;
    const QUOTE = 2;
    const APOS = 3;
    const WHITESPACE = 6;
    const EOL = 8;
    const CHAR = 9;
    const EOF = 0;
    const SOF = -1;

    protected $line_no = 1;
    protected $char_no = 0;
    protected $token = null;
    protected $token_type = -1;

    // Reader: read initial char, Context: save result data
    public function __construct(Reader $r, Context $context)
    {
        $this->r = $r;
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    // Read all white space
    public function eatWhiteSpace()
    {
        $ret = 0;
        if ($this->token_type != self::WHITESPACE &&
            $this->token_type != self::EOL) {
            return $ret;
        }
        while ($this->nextToken() == self::WHITESPACE ||
            $this->token_type == self::EOL) {
            $ret++;
        }
        return $ret;
    }

    // 获取当前令牌类型所对应的字符串或者由$int参数对应的类型
    public function getTypeString($int = -1)
    {
        if ($int < 0) {
            $int = $this->tokenType();
        }

        if ($int < 0) {
            return null;
        }

        $resolve = [
            self::WORD => 'WORD',
            self::QUOTE => 'QUOTE',
            self::APOS => 'APOS',
            self::WHITESPACE => 'WHITESPACE',
            self::EOL => 'EOL',
            self::CHAR => 'CHAR',
            self::EOF => 'EOF'
        ];
        return $resolve[$int];
    }

    public function tokenType()
    {
        return $this->token_type;
    }

    public function token()
    {
        return $this->token;
    }

    public function isWord()
    {
        return $this->token_type == self::WORD;
    }

    public function isQuote()
    {
        return $this->token_type == self::APOS || $this->token_type == self::QUOTE;
    }

    // line number
    public function line_no()
    {
        return $this->line_no;
    }

    // char location
    public function char_no()
    {
        return $this->char_no;
    }

    public function __clone()
    {
        $this->r = clone($this->r);
    }

    // 在源语句中前进到下一个令牌. 设置当前令牌并跟踪当前行数及字符
    public function nextToken()
    {
        $this->token = null;
//        $type;
        while (!is_bool($char=$this->getChar())) {
            if ($this->isEolChar($char)) {
                $this->token = $this->manageEolChars($char);
                $this->line_no++;
                $this->char_no = 0;
                $type = self::EOL;
                return ($this->token_type = self::EOL);
            } elseif ($this->isWordChar($char)) {
                $this->token = $this->eatWordChars($char);
                $type = self::WORD;
            } elseif ($this->isSpaceChar($char)) {
                $this->token = $char;
                $type = self::WHITESPACE;
            } elseif ($char == "'") {
                $this->token = $char;
                $type = self::APOS;
            } elseif ($char == '"') {
                $this->token = $char;
                $type = self::QUOTE;
            } else {
                $type = self::CHAR;
                $this->token = $char;
            }

            $this->char_no += strlen($this->token());
            return ($this->token_type = $type);
        }
        return ($this->token_type = self::EOF);
    }

    // 返回包含下一个令牌的类型和内容的数组
    public function peekToken()
    {
        $state = $this->getState();
        $type = $this->nextToken();
        $token = $this->token();
        $this->setState($state);
        return [$type, $token];
    }

    // 获得ScannerState对象, 它用来保存解析器的当前位置及当前令牌的数据
    public function getState()
    {
        $state = new ScannerState();
        $state->line_no = $this->line_no;
        $state->char_no = $this->char_no;
        $state->token = $this->token;
        $state->token_type = $this->token_type;
        $state->r = clone($this->r);
        $state->context = clone($this->context);
        return $state;
    }

    // 用ScannerState对象来恢复扫描器的状态
    public function setState(ScannerState $state)
    {
        $this->line_no = $state->line_no;
        $this->char_no = $state->char_no;
        $this->token = $state->token;
        $this->token_type = $state->token_type;
        $this->r = $state->r;
        $this->context = $state->context;
    }

    // 获得源语句中的下一个字符.
    private function getChar()
    {
        return $this->r->getChar();
    }

    // 获得非单词字符之前的所有字符
    private function eatWordChars($char)
    {
        $val = $char;
        while ($this->isWordChar($char = $this->getChar())) {
            $val .= $char;
        }
        if ($char) {
            $this->pushBackChar();
        }
        return $val;
    }

    // 获得非空白字符之前的所有字符
    private function eatSpaceChars($char)
    {
        $val = $char;
        while ($this->isSpaceChar($char = $this->getChar())) {
            $val .= $char;
        }
        $this->pushBackChar();
        return $val;
    }

    // 在源语句中移动到前一个字符
    private function pushBackChar()
    {
        $this->r->pushBackChar();
    }

    private function isWordChar($char)
    {
        return preg_match("/[A-Za-z0-9_\-]/", $char);
    }

    private function isSpaceChar($char)
    {
        return preg_match("/\t| /", $char);
    }

    private function isEolChar($char)
    {
        return preg_match("/\n|\r/", $char);
    }

    // 处理\n, \r或\r\n
    private function manageEolChars($char)
    {
        if ($char == "\r") {
            $next_char = $this->getChar();
            if ($next_char == "\n") {
                return "{$char}{$next_char}";
            } else {
                $this->pushBackChar();
            }
        }
        return $char;
    }

    public function getPos()
    {
        return $this->r->getPos();
    }
}

class ScannerState
{
    public $line_no;
    public $char_no;
    public $token;
    public $token_type;
    public $r;
    public $context;
}

/**
 * 类中最核心的方法就是nextToken(), 它将尝试匹配给定字符串中的下一个令牌.
 *
 * Scanner存储着一个Context对象.
 *
 * Parser对象使用Context对象来获取解析完目标文本后的结果.
 *
 * 对于ScannerState类, Scanner被设置为便于Parser对象存储状态, 获取状态, 并且当Parser执行出错时还能恢复状态.
 *
 * getState()方法填充并返回ScannerState对象, 而在需要时setState()则会使用ScannerState对象重置状态.
 */


/**
 * 供解析器使用的信息交流点.
 */
class Context
{
    public $resultstack = [];

    public function pushResult($mixed)
    {
        array_push($this->resultstack, $mixed);
    }

    public function popResult()
    {
        return array_pop($this->resultstack);
    }

    public function resultCount()
    {
        return count($this->resultstack);
    }

    public function peekResult()
    {
        if (empty($this->resultstack)) {
            throw new \Exception('empty resultstack');
        }
        return $this->resultstack[count($this->resultstack) - 1];
    }
}

/**
 * Scanner自身并不直接操作文件或字符串, 而是用Reader对象来代替自己执行操作.
 *
 * 这样可以轻松的切换数据源
 */

abstract class Reader
{
    abstract public function getChar();
    abstract public function getPos();
    abstract public function pushBackChar();
}

class StringReader extends Reader
{
    private $in;
    private $pos;

    public function __construct($in)
    {
        $this->in = $in;
        $this->pos = 0;
    }

    public function getChar()
    {
        if ($this->pos >= strlen($this->in)) {
            return false;
        }
        $char = substr($this->in, $this->pos, 1);
        $this->pos++;
        return $char;
    }

    public function getPos()
    {
        return $this->pos;
    }

    public function pushBackChar()
    {
        $this->pos--;
    }

    public function string()
    {
        return $this->in;
    }
}

/**
 * Demo
 */
$context = new Context();
$user_in = "\$input equals '4' or \$input equals 'four'";
$reader = new StringReader($user_in);
$scanner = new Scanner($reader, $context);

while ($scanner->nextToken() != Scanner::EOF) {
//    var_dump($scanner->token(), $scanner->char_no(), $scanner->getTypeString());
    print $scanner->token();
    print "\t{$scanner->char_no()}";
    print "\t{$scanner->getTypeString()}\n";
}

