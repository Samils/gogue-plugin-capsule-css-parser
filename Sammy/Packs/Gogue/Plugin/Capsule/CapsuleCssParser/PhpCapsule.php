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

      $blockVariableDeclarationRe = '/\-\-([a-zA-Z0-9_-]+)\s*:\s*(.+);*/';

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

        if (preg_match_all ($blockVariableDeclarationRe, $componentData ['block'], $variableDeclarationMatches)) {
          foreach ($variableDeclarationMatches [1] as $varNameIndex => $varName) {

            $varValue = $variableDeclarationMatches [2][$varNameIndex];

            if (!$varValue) {
              $varValue = 'null';
            }

            $varValue = join ('', [
              '\'',
              preg_replace ('/\s*;\s*$/', '', $varValue),
              '\''
            ]);

            $varName = $this->rewriteComponentArgumentName (trim ($varName));

            $argumentCode = "\t\$scope->$varName = $varValue;";

            array_push ($arguments, $argumentCode);
          }
        }

        $componentData ['block'] = preg_replace ($blockVariableDeclarationRe, '', $componentData ['block']);

        $argumentNameList = [];

        #print_r ($componentData);

        foreach ($componentData ['componentArguments'] as $argumentName => $argumentDefaultValue) {

          $argumentName = $this->rewriteComponentArgumentName ($argumentName);

          if (!$argumentDefaultValue) {
            $argumentDefaultValue = 'null';
          }

          $argumentCode = "\t\$scope->$argumentName = !(isset (\$args ['$argumentName'])) ? $argumentDefaultValue : \$args ['$argumentName'];";

          array_push ($argumentNameList, "'$argumentName'");
          array_push ($arguments, $argumentCode);
        }

        $componentStyleData = join ('', [
          $componentData ['componentTagName'],
          $componentData ['componentTagSelector'],
          '\'.$scope->componentSelectorReference.\'',
          $componentData ['block']
        ]);

        $argumentNameList = join (',', array_merge ($argumentNameList, ['\'children\'']));

        if (in_array (strtolower ($componentData ['componentTagName']), ['global'])) {
          $componentStyles = $this->generateGlobalStyles ($componentData ['block']);

          array_push ($phpCapsuleCode, join ("\n", [
            "Capsule::Def ('{$componentData['componentName']}', function (\$args, CapsuleScopeContext \$scope) {",
            join ("\n", $arguments),
            "\n\t\$scope->componentSelectorReference = call_user_func ('App\View\generateComponentSelectorRef', '{$componentData['componentName']}');",
            "\treturn Capsule::PartialRender ('Fragment', [], Capsule::CreateElement ('head', [], Capsule::CreateElement ('style', ['data-styled-component' => '{$componentData['componentName']}', 'data-styled-component-id' => \$scope->componentSelectorReference, 'type' => 'text/css'], function (\$args, CapsuleScopeContext \$scope) {return '$componentStyles';})));",
            "});\n",

            "Capsule::Export ('{$componentData['componentName']}');\n\n"
          ]));
        } else {
          $componentStyles = $this->generateStyles ($componentStyleData);

          array_push ($phpCapsuleCode, join ("\n", [
            "Capsule::Def ('{$componentData['componentName']}', function (\$args, CapsuleScopeContext \$scope) {",
            join ("\n", $arguments),
            "\n\t\$scope->componentSelectorReference = call_user_func ('App\View\generateComponentSelectorRef', '{$componentData['componentName']}');",
            "\treturn Capsule::PartialRender ('Fragment', [], Capsule::CreateElement ('head', [], Capsule::CreateElement ('style', ['data-styled-component' => '{$componentData['componentName']}', 'data-styled-component-id' => \$scope->componentSelectorReference, 'type' => 'text/css'], function (\$args, CapsuleScopeContext \$scope) {return '$componentStyles';})), Capsule::CreateElement ('{$componentData['componentTagName']}', array_merge (ArrayHelper::PropsBeyond ([{$argumentNameList}], \$args), ['{$componentData['componentTagSelectorAttribute']}' => join (' ', [\$scope->componentSelectorReference, ((isset (\$args ['{$componentData['componentTagSelectorAttribute']}']) && is_string (\$args ['{$componentData['componentTagSelectorAttribute']}'])) ? \$args ['{$componentData['componentTagSelectorAttribute']}'] : '')])]), Capsule::Yield (null, [])));",
            "});\n",

            "Capsule::Export ('{$componentData['componentName']}');\n\n"
          ]));
        }

        # REVIEW
        # $componentStyles = preg_replace_callback ($strRe, [$this, 'formatStrData'], $componentStyles);


      }

      return join ('', $phpCapsuleCode);
    }

    /**
     * Rewrite component argument name
     */
    private function rewriteComponentArgumentName ($argumentName) {
      return lcfirst (preg_replace ('/\s+/', '', ucwords (preg_replace ('/\-+/', ' ', $argumentName))));
    }

    /**
     * formatStrData
     */
    private function formatStrData ($match) {
      return "'.call_user_func (function (\$str) {return \"'\$str'\";}, {$match [0]}).'";
    }
  }}
}
