<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Support;

class AnsiMatcher
{
    /**
     * @link https://github.com/chalk/ansi-regex/blob/main/index.js
     */
    protected function regex(): string
    {
        // @TODO
        // Valid string terminator sequences are BEL, ESC\, and 0x9c
        $terminators = '(?:\\x07|\\e\\\\|\\x9C)';
        return '/'
            . '('
            . '[\\e\\x9B][\\[\\]()#;?]*(?:(?:(?:(?:;[-a-zA-Z\\d\\/\\#&.:=?%@~_]+)*|[a-zA-Z\\d]+(?:;[-a-zA-Z\\d\\/\\#&.:=?%@~_]*)*)?' . $terminators . ')'
            . '|'
            . '(?:(?:\\d{1,4}(?:;\\d{0,4})*)?(?:[\\dA-PR-TZcf-nq-uy=><~])))'
            . ')'
            . '/x';

    }
}