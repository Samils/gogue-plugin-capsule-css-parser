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
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\PhpCapsule')) {
  /**
   * @trait PhpCapsule
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
  trait PhpCapsule {
    /**
     * '/::=array-block([0-9]+)::/i'
     *
     */
    private function generatePhpCapsuleCode (array $componentDatas) {
      $phpCapsuleCode = [];

      /**
       * $componentData
       *  [componentName] => string
       *  [componentTagName] => string
       *  [componentTagSelector] => set('.', '#')
       *  [componentTagSelectorAttribute] => set('class', 'id')
       *  [componentArguments] => Array
       */
      foreach ($componentDatas as $componentData) {
        $componentData = array_merge ([
          'componentName' => '',
          'componentTagName' => '',
          'componentTagSelector' => '.',
          'componentTagSelectorAttribute' => 'div',
          'componentArguments' => []
        ], $componentData);

        $arguments = [];

        foreach ($componentData ['componentArguments'] as $argumentName => $argumentDefaultValue) {

          $argumentName = lcfirst (preg_replace ('/\s+/', '', ucwords (preg_replace ('/\-+/', ' ', $argumentName))));

          if (!$argumentDefaultValue) {
            $argumentDefaultValue = 'null';
          }

          $argumentCode = "\t\$scope->$argumentName = !(isset (\$args ['$argumentName'])) ? $argumentDefaultValue : \$args ['$argumentName'];";

          array_push ($arguments, $argumentCode);
        }

        $componentStyles = '';

        array_push ($phpCapsuleCode, join ("\n", [
          "Capsule::Def ('{$componentData['componentName']}', function (\$args, CapsuleScopeContext \$scope) {",
          join ("\n", $arguments),
          "\n\t\$scope->componentSelectorReference = call_user_func ('App\View\generateComponentSelectorRef', '{$componentData['componentName']}');",
          "\treturn Capsule::PartialRender ('Fragment', [], Capsule::CreateElement ('style', ['type' => 'text/css'], '$componentStyles'), Capsule::CreateElement ('{$componentData['componentTagName']}', ['{$componentData['componentTagSelectorAttribute']}' => \$scope->componentSelectorReference], Capsule::Yield (null, [])));",
          "});\n",

          "Capsule::Export ('{$componentData['componentName']}');\n\n"
        ]));
      }

      return join ('', $phpCapsuleCode);
    }
  }}
}
