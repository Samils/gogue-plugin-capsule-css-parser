<?php
/**
 * @version 2.0
 * @author Sammy
 *
 * @keywords Samils, ils, php framework
 * -----------------
 * @package Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser
 * - Autoload, application dependencies
 *
 * MIT License
 *
 * Copyright (c) 2020 Ysare
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser {
  use Sammy\Packs\Gogue\Component\Code\BlockEncoder;
  /**
   * Make sure the module base internal trait is not
   * declared in the php global scope defore creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\PhpCapsuleStyle')) {
  /**
   * @trait PhpCapsuleStyle
   * Base internal trait for the
   * Gogue\Plugin\Capsule\CapsuleCssParser module.
   * -
   * This is (in the ils environment)
   * an instance of the php module,
   * wich should contain the module
   * core functionalities that should
   * be extended.
   * -
   * For extending the module, just create
   * an 'exts' directory in the module directory
   * and boot it by using the ils directory boot.
   * -
   */
  trait PhpCapsuleStyle {
    /**
     * Generate component stylesheet from its
     * data object
     */
    private function generateStyles ($block, $parentReference = '') {
      if (!(is_string ($parentReference) && $parentReference)) {
        $parentReference = is_array ($parentReference) ? join ('', $parentReference) : '';
      }

      $currentReference = '';
      $blockEncoder = new BlockEncoder;

      if (preg_match ('/^([^\{]+)/', $block, $match)) {

        $specialCharRe = '/^\s*(\&):/';
        $currentReference = ' ' . trim ($match [0]);

        $specialCharsMap = [
          '&' => ''
        ];

        if (preg_match ($specialCharRe, $currentReference, $specialCharMatch)) {

          $replacement = join ('', [$specialCharsMap [$specialCharMatch [1]], ':']);

          $currentReference = preg_replace ($specialCharRe, $replacement, $currentReference);

          $block = preg_replace ($specialCharRe, $replacement, trim ($block));

          $parentReference = trim ($parentReference);
        }

        $nestedBlocksParentRef = join ('', [
          $parentReference, $currentReference
        ]);
      }

      $block = preg_replace ('/^([^\{]+)\s*\{\s*/', '', $block);
      $block = preg_replace ('/(\s*\}\s*)$/', '', $block);

      # $block = $componentData ['block'];

      $nestedBlockRe = '/(.+)\s*::=block-block([0-9]+)::/i';
      $mediaQueryRe = '/(\@media(\s+.+)?(::=group-block([0-9]+)::(\s+and)?)*)(::=block-block([0-9]+)::)/i';

      /**
       * get whole the mediaQueryRe from the current block
       * content
       */
      preg_match_all ($mediaQueryRe, $block, $mediaQueryMatches);

      if (!empty ($parentReference)) {
        $parentReference = $parentReference . ' ';
      }

      $block = preg_replace ($mediaQueryRe, '', $block);

      /**
       * get whole the nestedBlockRe from the current block
       * content
       */
      preg_match_all ($nestedBlockRe, $block, $nestedBlocksMatches);

      $block = preg_replace ($nestedBlockRe, '', $block);

      $block = preg_replace_callback ('/([a-zA-Z0-9_\-]+)\:\s*([^;]+)/', [$this, 'formatStyleKeyValuePair'], $block);

      $block = join ('', [
        $parentReference, $currentReference, '{', $block, '}'
      ]);

      if (is_array ($nestedBlocksMatches [0]) && count ($nestedBlocksMatches [0]) >= 1) {
        foreach ($nestedBlocksMatches [0] as $nestedBlock) {
          $block .= $this->generateStyles ($blockEncoder->decodeBlock ($nestedBlock, $this->store), $nestedBlocksParentRef . ' ');
        }
      }

      if ($mediaQueryMatches && count ($mediaQueryMatches) >= 1) {

        foreach ($mediaQueryMatches [1] as $mediaQueryIndex => $mediaQuery) {
          $mediaQueryBlock = $mediaQueryMatches [6][$mediaQueryIndex];

          $mediaQueryBlock = $blockEncoder->decodeBlock ($mediaQueryBlock, $this->store);

          $mediaQueryBlock = join ('', [$nestedBlocksParentRef, $mediaQueryBlock]);

          #exit ($mediaQueryBlock);

          $mediaQueryBlockStyles = $this->generateStyles ($mediaQueryBlock);

          #echo "\n\nBlock => ", $mediaQueryBlock, "\nresult => ", $mediaQueryBlockStyles, "\n\n\n\n\n\n\n\n";
          #echo "\n\n\n\n\n", join ('', [$mediaQuery, '{', $mediaQueryBlockStyles, '}']), "\n\n\n\n\n\n\n\n";
          $block .= join ('', [$mediaQuery, '{', $mediaQueryBlockStyles, '}']);

        }

        #echo "current ref => $currentReference\nParent Ref => $parentReference\n\n";

        #print_r($mediaQueryMatches);

        #exit (0);
      }

      $block = $this->minifyCss ($block);

      $block = $this->parseCssVarRefs ($block);

      return $blockEncoder->decodeBlocks ($block, $this->store);
    }

    /**
     * @method string
     *
     * Generate global styles
     */
    private function generateGlobalStyles ($block) {
      $blockStyles = $this->generateStyles (' ' . $block);

      return preg_replace ('/\s*\{\s*\}\s*/', '', $blockStyles);
    }

    /**
     * Minify css
     */
    private function minifyCss (string $cssCode) {
      $cssCode = preg_replace ('/\s+/', ' ', $cssCode);

      $re = '/\s*([,;:\[\]\(\)\{\}])\s*/';
      /* done
        $minifyRewriteStepList = [
          # :
          '/\s+:/' => ':',
          '/:\s+/' => ':',
          # {
          '/\s+\{/' => '{',
          '/\{\s+/' => '{',
          # }
          '/\s+\}/' => '}',
          '/\}\s+/' => '}',
          # [
          '/\s+\[/' => '[',
          '/\[\s+/' => '[',
          # ]
          '/\s+\]/' => ']',
          '/\]\s+/' => ']',
          # ;
          '/\s+;/' => ';',
          '/;\s+/' => ';',
          # ,
          '/\s+,/' => ',',
          '/,\s+/' => ',',
        ];

        foreach ($minifyRewriteStepList as $re => $replacement) {
          $cssCode = preg_replace ($re, $replacement, $cssCode);
        }
      */

      $cssCode = preg_replace_callback ($re, function ($match) {
        return trim ($match [1]);
      }, $cssCode);

      return $cssCode;
    }

    private function formatStyleValue ($value) {
      $re = '/^(([a-zA-Z0-9_\-\s\#]+)|var\s*::=group-block([0-9]+)::)$/';

      if (preg_match ($re, trim ($value))) {
        return $value;
      }

      $strRe = '/::\$([0-9]+):/';
      $styleValueMatch = preg_replace_callback ($strRe, [$this, 'formatStrData'], $value);

      return "'.call_user_func (function (\$str) {return \$str;}, '{$styleValueMatch}').'";
    }

    private function formatStyleKeyValuePair ($match) {

      $styleKeyToRewrite = [
        'border-radius',
        'background-size',
        'background-clip'
      ];

      $styleKeyPrefixList = [
        '', '-webkit-', '-moz-', '-o-', '-ms-'
      ];


      $key = strtolower (trim ($match [1]));
      $value = ($match [2]);

      $keyToValiableName = $this->rewriteComponentArgumentName ($key);

      $keyParserMethodRef = join ('', ['read', $keyToValiableName, 'StyleKey']);

      if (method_exists ($this, $keyParserMethodRef)) {
        $value = call_user_func_array ([$this, $keyParserMethodRef], [$value]);
      } else {
        $value = $this->formatStyleValue ($value);
      }

      if (in_array ($key, $styleKeyToRewrite)) {
        $styleKeyValuePairs = [];

        foreach ($styleKeyPrefixList as $styleKeyPrefix) {
          $styleKey = join ('', [$styleKeyPrefix, $key]);

          array_push ($styleKeyValuePairs, join (':', [$styleKey, $value]));
        }

        return join(';', $styleKeyValuePairs);
      }

      return join (':', [$key, $value]);
    }
  }}
}
