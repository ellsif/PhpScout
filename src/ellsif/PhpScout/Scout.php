<?php
namespace ellsif\PhpScout;

class Scout
{
    const SCOPES = ['public', 'protected', 'private'];

    private $buffer = '';
    private $data = null;

    private $lastComment = '';

    /**
     * read php file, return  info of functions
     *
     * ## Parameters
     *
     * ## Return Values
     *
     * ## Examples
     *     $scout = new Scout();
     *     $data = $scout->scout('/path/to/php/sample.php');
     *
     * ##
     */
    public function scout($phpFilePath): array
    {
        $this->buffer = '';
        $this->data = ['functions' => []];
        $this->lastComment = '';

        if (!file_exists($phpFilePath)) {
            throw new \InvalidArgumentException("${phpFilePath} not found.");
        }

        $fp = fopen($phpFilePath, 'r');
        if (!$fp) {
            throw new \RuntimeException("${phpFilePath} open failed.");
        }

        $this->data = ['functions' => []];

        $keep = $this->skipTo($fp, "<?php");

        while ($keep) {

            if (($char = $this->skipWhiteSpace($fp)) === false) {
                break;
            }
            if ($char !== ';') {
                $this->buffer .= $char;
            }

            if (in_array($char, ['/', '"', "'", ';', '{'])) {
                $lastChar = $char;
            } else {
                $lastChar = $this->bufferTo($fp, ['/', '"', "'", ';', '{']);
            }

            $sentence = $this->buffer;

            if ($lastChar == ';' && mb_strpos(trim($sentence), 'namespace') === 0) {
                $this->setNamespace($sentence);
                $this->buffer = '';
                continue;
            } elseif ($lastChar == ';' && mb_strpos(trim($sentence), 'use') === 0) {
                $this->setUse($sentence);
                $this->buffer = '';
                continue;
            }

            if ($lastChar === '"' || $lastChar === "'") {
                $this->buffer .= $lastChar;
                if (!$this->bufferTo($fp, $lastChar)) {
                    break;
                }
                $this->buffer .= $lastChar;
            } elseif($lastChar === '{' && $this->checkContain($this->buffer, ['class', 'interface', 'trait'])) {
                $this->scoutClass();
            } elseif ($lastChar === '{' || mb_strpos($this->buffer, 'function') !== FALSE) {
                if (!$this->scoutMethod($fp, $lastChar)) {
                    break;
                }
            } elseif ($lastChar === ';') {
                $this->scoutPropery($sentence);
                $this->buffer = '';
            } elseif ($lastChar === '/') {
                if (!$this->scoutComment($fp)) {
                    break;
                }
            }
        }
        fclose($fp);
        return $this->data;
    }

    /**
     * skip ws. return first character non-ws.
     */
    private function skipWhiteSpace(&$fp)
    {
        while(($char = fgetc($fp)) !== false) {
            $code = ord($char);
            if ($code >= 33) {
                return $char;
            }
        }
        return false;
    }

    private function scoutClass()
    {
        $define = trim($this->buffer);
        $words = explode(' ', $define);

        if (($idx = array_search('class', $words)) !== FALSE) {
            $absIdx = array_search('abstract', $words);
            $this->data['class'] = [
                'name' => $words[$idx + 1],
                'define' => $define,
                'comment' => $this->lastComment,
                'abstract' => $absIdx !== FALSE && $absIdx < $idx,
            ];
        } elseif (($idx = array_search('interface', $words)) !== FALSE) {
            $this->data['interface'] = [
                'name' => $words[$idx + 1],
                'define' => $define,
                'comment' => $this->lastComment,
            ];
        } elseif (($idx = array_search('trait', $words)) !== FALSE) {
            $this->data['trait'] = [
                'name' => $words[$idx + 1],
                'define' => $define,
                'comment' => $this->lastComment,
            ];
        }
        $this->buffer = '';

        return true;
    }

    private function scoutMethod(&$fp, $char)
    {
        $define = trim($this->buffer);

        $abstract = $this->isAbstract($define);
        $code = null;
        if ($char === '{') {
            $this->buffer .= $char;
            $this->bufferToBlockEnd($fp);
            $code = $this->modifCodeIndent($this->buffer);
        }
        if (($function = $this->getFunction($define))) {
            $this->addFunction([
                'abstract' => $abstract,
                'scope' => $this->getScope($define),
                'name' => $this->getFunction($define),
                'comment' => $this->lastComment,
                'code' => $code,
                'define' => $define,
            ]);
        }
        $this->buffer = '';

        return true;
    }

    private function scoutPropery($sentence)
    {
        $words = explode(' ', trim($sentence));
        if (count($words) < 2) {
            return;
        }
        $scope = in_array($words[0], Scout::SCOPES) ? $words[0] : null;
        $const = ($words[0] === 'const');
        $isNoSpace = (mb_strpos('=', $words[1]) === FALSE);
        $prop = $isNoSpace ? $words[1] : $this->getProperyName($words[1]);
        $value = $this->getProperyValue(implode(' ', array_slice($words, 2)));
        $this->data['properties'] = $this->data['properties'] ?? [];
        $this->data['properties'][] = [
            'const' => $const,
            'scope' => $scope,
            'name' => $prop,
            'value' => $value,
        ];
    }

    private function getProperyName($str)
    {
        $words = explode('=', $str);
        return trim($words[0]);
    }

    private function getProperyValue($str)
    {
        $words = explode('=', $str);
        return trim($words[1] ?? '');
    }

    private function scoutComment(&$fp)
    {
        $char = fgetc($fp);
        if ($char === '/') {
            // line comment
            $this->buffer = '';
            $this->bufferTo($fp, PHP_EOL);
            $this->lastComment = trim($this->buffer);
        } else if ($char === '*') {
            // block comment
            $this->bufferTo($fp, "*/");

            $ary = explode(PHP_EOL, trim($this->buffer));
            $lines = [];
            foreach ($ary as $line) {
                $lines[] = $this->trimComment($line ?? '');
            }

            $this->lastComment = trim(implode(PHP_EOL, $lines));
        } else {
            return false;
        }
        $this->buffer = '';
        return true;
    }

    private function skipTo(&$fp, $str): bool
    {
        $len = mb_strlen($str);
        $machLen = 0;
        while(($char = fgetc($fp)) !== false) {
            if ($char === mb_substr($str, $machLen, 1)) {
                $machLen++;
            }
            if ($machLen >= $len) {
                return true;
            }
        }
        return false;
    }

    // read to buffer and return last character
    private function bufferTo(&$fp, $terminates)
    {
        $coBuffer = '';
        $len = [];
        $machLen = [];
        if (!is_array($terminates)) {
            $terminates = [$terminates];
        }
        foreach ($terminates as $str) {
            $len[] = mb_strlen($str);
            $machLen[] = 0;
        }
        while(($char = fgetc($fp)) !== false) {
            $coBuffer .= $char;
            for ($i = 0; $i < count($len); $i++) {
                if ($char === mb_substr($terminates[$i], $machLen[$i], 1)) {
                    $machLen[$i]++;
                } else {
                    $machLen[$i] = 0;
                }
                if ($machLen[$i] >= $len[$i]) {
                    return $terminates[$i];
                }
            }
            $this->buffer .= $coBuffer;
            $coBuffer = '';
        }
        return $char;
    }

    private function bufferToBlockEnd(&$fp)
    {
        $depth = 1;
        while(($terminate = $this->bufferTo($fp, ['{', '}', '"', "'", "/"])) !== false) {
            $this->buffer .= $terminate;
            if ($terminate === '{') {
                $depth++;
            } elseif ($terminate === '}') {
                $depth--;
            } elseif ($terminate === '"' || $terminate === "'") {
                $this->buffer .= $this->bufferTo($fp, $terminate);
            } elseif ($terminate === '/') {
                if (($next = fgetc($fp)) === false){
                    return;
                }
                $this->buffer .= $next;
                if ($next === '/') {
                    $this->buffer .= $this->bufferTo($fp, PHP_EOL);
                } elseif ($next === '*') {
                    $this->buffer .= $this->bufferTo($fp, "*/");
                }
            }
            if ($depth <= 0) {
                break;
            }
        }
    }

    private function addFunction($func)
    {
        if (!is_array($this->data['functions'])) {
            $this->data['functions'] = [];
        }
        $this->data['functions'][] = $func;
    }

    private function getFunction($define): string
    {
        $pos = mb_strpos($define, 'function ');
        $endPos = mb_strpos($define, '(', $pos);
        return trim(mb_substr($define, $pos + 9, ($endPos) - ($pos + 9)));
    }

    private function getScope($define): string
    {
        if (mb_strpos($define, 'public ') !== false) {
            return 'public';
        } elseif (mb_strpos($define, 'private ') !== false) {
            return 'private';
        } elseif (mb_strpos($define, 'protected ') !== false) {
            return 'protected';
        }
        return '';
    }

    private function isAbstract($define): bool
    {
        return (mb_strpos($define, 'abstract ') !== false);
    }

    private function modifCodeIndent($code): string
    {
        $lines = explode("\n", $code );
        $newLines = [];
        $remove = 0;
        foreach($lines as $line) {
            $diff = mb_strlen($line) - mb_strlen(ltrim($line, ' '));
            if ($diff > 0) {
                $remove = $diff;
                break;
            }
        }
        if ($remove > 0) {
            foreach($lines as $line) {
                $newLines[] = $this->removePrefix($line, ' ', $remove);
            }
        } else {
            return $code;
        }
        return implode("\n", $newLines);
    }

    private function removePrefix($line, $char, $remove)
    {
        if (mb_strpos($line, str_repeat($char, $remove)) === 0) {
            return mb_substr($line, $remove);
        }
        return $line;
    }

    // remove left '* ' of block comment
    private function trimComment($line): string
    {
        $pos = mb_strpos($line, '*');
        if ($pos === false) {
            return $line;
        }
        if (mb_substr($line, $pos + 1, 1) === ' ') {
            return mb_substr($line, $pos + 2);    // "* comment" -> "comment"
        }
        return mb_substr($line, $pos + 1);    // "*comment" -> "comment"
    }

    private function setNamespace($namespaceLine)
    {
        $array = explode(' ', $namespaceLine);
        $this->data['namespace'] = end($array);
    }

    private function setUse($useLine)
    {
        $array = explode(' ', $useLine);
        if (!isset($this->data['use'])) {
            $this->data['use'] = [];
        }
        $this->data['use'][] = end($array);
    }

    private function checkContain($str, $needles)
    {
        if (!is_array($needles)) $needles = [$needles];
        foreach($needles as $needle) {
            if (mb_strpos($str, $needle) !== FALSE) return true;
        }
        return false;
    }
}
