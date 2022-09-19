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
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\CssVariables')) {
  /**
   * @trait CssVariables
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
  trait CssVariables {
    /**
     *
     * parse css variable references
     */
    private function parseCssVarRefs ($cssCode) {
      $cssVariableRefRe = '/var\s*::=group-block([0-9]+)::/i';

      $cssCode = preg_replace_callback ($cssVariableRefRe, [$this, 'cssVariableRefMatch'], $cssCode);

      return $cssCode;
    }

    private function cssVariableRefMatch ($match) {
      $blockEncoder = new BlockEncoder;

      $variableRef = $blockEncoder->decodeBlocks (trim ($match [0]), $this->store);

      $re = '/(^var\s*\(|(\s*\))$)/i';
      $variableRef = preg_replace ($re, '', $variableRef);

      $variableRef = preg_replace ('/^\-+/', '', trim ($variableRef));

      $variableName = $this->rewriteComponentArgumentName ($variableRef);

      $variablePropRefs = preg_split ('/\./', $variableName);

      if (count ($variablePropRefs) <= 1) {
        return '\'.(!is_null ($scope->'.$variableName.') ? $scope->'.$variableName.' : \'inherit\').\'';
      }

      $variableName = join ('->', [
        '$scope', $variablePropRefs [0]
      ]);

      $variablePropRefMap = function ($ref) {
        $ref = preg_replace ('/\s+/', '', trim ($ref));

        $variableRefRe = '/^(\$([a-zA-Z_])(.*))$/';

        if (!preg_match ($variableRefRe, $ref)) {
          return $ref;
        }

        $ref = preg_replace ('/^\$(scope\->)?/', '', $ref);

        return "'.(\$scope->$ref).'";
      };

      $variablePropRef = join ('.', array_map ($variablePropRefMap, array_slice ($variablePropRefs, 1, count ($variablePropRefs))));

      return '\'.(!is_null ('.$variableName.') ? ObjectHelper::ReadProperty ('.$variableName.', \''.$variablePropRef.'\') : \'inherit\').\'';

    }
  }}
}
