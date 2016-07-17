<?php
/**
 * @author nesfoubaer
 * @date 16/6/17 下午8:53
 */

namespace niceforbear\utils;


class Demo
{
    public function arrayEx12(){
        $handle = opendir('.');
        while (false !== ($file = readdir($handle))) {
            $files[] = $file;
        }
        closedir($handle);
    }

    public function createFuncEx2(){
        $createFuncParams = ['', ''];
        $code = '';
        $parameters = [];

        $createFuncArgs = implode(', ', $createFuncParams);
        $createFunc = create_function($createFuncArgs, $code);
        call_user_func($createFunc, $parameters);
    }

    public function reservedKeywords1(){
        $str = <<<EOT
RegEx to find all the keywords:

\b(
(a(bstract|nd|rray|s))|
(c(a(llable|se|tch)|l(ass|one)|on(st|tinue)))|
(d(e(clare|fault)|ie|o))|
(e(cho|lse(if)?|mpty|nd(declare|for(each)?|if|switch|while)|val|x(it|tends)))|
(f(inal|or(each)?|unction))|
(g(lobal|oto))|
(i(f|mplements|n(clude(_once)?|st(anceof|eadof)|terface)|sset))|
(n(amespace|ew))|
(p(r(i(nt|vate)|otected)|ublic))|
(re(quire(_once)?|turn))|
(s(tatic|witch))|
(t(hrow|r(ait|y)))|
(u(nset|se))|
(__halt_compiler|break|list|(x)?or|var|while)
)\b
EOT;
    }

    public function constants(){
        echo PHP_VERSION, PHP_OS, PHP_SAPI, PHP_INT_MAX, PHP_INT_SIZE, DEFAULT_INCLUDE_PATH,
        PEAR_INSTALL_DIR, PEAR_EXTENSION_DIR;
    }

    public function make(){
//        静态声明是在编译时解析的。
    }
}

$demo = new Demo();
$demo->constants();