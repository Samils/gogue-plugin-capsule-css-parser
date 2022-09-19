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
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\Components')) {
  /**
   * @trait Components
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
  trait Components {
    /**
     * '/::=array-block([0-9]+)::/i'
     *
     */
    private function getComponentDeclarations ($code) {
      $componentDeclarationRe = '/(.+)\s*(::=(array|group)-block([0-9]+)::)*\s*(::=block-block([0-9]+)::)/';

      preg_match_all ($componentDeclarationRe, $code, $componentDeclarations);

      if (!(count ($componentDeclarations [1]) >= 1)) {
        return [];
      }

      $declarationMap = [];

      foreach ($componentDeclarations [1] as $i => $declaration) {
        $declarationMap [trim ($declaration)] = trim ($componentDeclarations [5][$i]);
      }

      return array_merge ($componentDeclarations, [
        'declarations' => $componentDeclarations [1],
        'blocks' => $componentDeclarations [5],
        'declarationMap' => $declarationMap
      ]);
    }

    /**
     * Run the 'getComponentDeclarationDatas' method
     * for getting the name of the component and some
     * other datas such as the arguments, the html
     * tag to be generated and the type of selector
     * for the generated html element returning the
     * created selector for that tag associated to
     * the declaration index.
     *
     * @return array
     * [
     *   'componentName' => string
     *   'componentTagName' => string
     *   'componentTagSlector' => set('.', '#')
     *   'componentArguments' => array
     * ]
     */
    private function getComponentDeclarationDatas (array $declarations) {
      /**
       * Component declaration datas
       */
      $componentDeclarationDatas = [];
      /**
       *
       */
      foreach ($declarations as $declaration) {
        $declaration = trim ($declaration);

        $componentTagNameRe = '/^([a-zA-Z0-9_-]+)(\.|\#)/';
        $componentArgsRe = '/(::=array-block([0-9]+)::\s*)+$/i';
        $componentParentsRe = '/(::=group-block([0-9]+)::\s*)+$/i';

        $componentDatas = [
          'componentName' => null,
          'componentTagName' => 'div',
          'componentTagSelector' => '.',
          'componentTagSelectorAttribute' => 'class',
          'componentArguments' => [],
          'componentParents' => []
        ];

        if (preg_match ($componentArgsRe, $declaration, $componentArgsMatch)) {
          $componentDatas ['componentArguments'] = $this->readComponentArguments ($componentArgsMatch);

          $declaration = preg_replace ($componentArgsRe, '', $declaration);
        }

        if (preg_match ($componentParentsRe, $declaration, $componentParentsMatch)) {
          $componentDatas ['componentParents'] = $this->readComponentParents ($componentParentsMatch);

          $declaration = preg_replace ($componentParentsRe, '', $declaration);
        }

        if (preg_match ($componentTagNameRe, $declaration, $componentTagNameMatch)) {
          $componentDatas ['componentTagName'] = $componentTagNameMatch [1];
          $componentDatas ['componentTagSelector'] = $componentTagNameMatch [2];
        }

        $componentDatas ['componentTagSelectorAttribute'] = $componentDatas ['componentTagSelector'] == '#' ? 'id' : 'class';

        $componentDatas ['componentName'] = preg_replace ($componentTagNameRe, '', $declaration);

        array_push ($componentDeclarationDatas, $componentDatas);
      }

      return $componentDeclarationDatas;
    }

    /**
     * read component parents
     */
    private function readComponentParents ($parentsRef) {
      $blockEncoder = new BlockEncoder;

      $parents = [];
      $parentsGroup = trim ($parentsRef [0]);

      $parentsGroup = trim ($blockEncoder->decodeBlock ($parentsGroup, $this->store));

      $parentsGroup = preg_replace ('/^\(\s*/', '', preg_replace ('/(\s*\))$/', '', $parentsGroup));

      $parentsGroupContent = preg_split ('/(\s*,\s*)+/', $parentsGroup);

      return array_map (function ($parent) {
        return trim ((string)($parent));
      }, $parentsGroupContent);
    }

    /**
     * read component arguments
     */
    private function readComponentArguments ($argumentsRef) {
      $blockEncoder = new BlockEncoder;

      $arguments = [];
      $argumentsBlock = trim ($argumentsRef [0]);

      $argumentsBlock = $blockEncoder->decodeBlocks ($argumentsBlock, $this->store);

      # >>: rewrite
      $argumentsList = preg_split ('/\s*,\s*/',
        preg_replace ('/^(\s*\[\s*)/', '', preg_replace ('/(\s*\]\s*)$/', '', $argumentsBlock))
      );

      foreach ($argumentsList as $argument) {
        $argumentValuePair = preg_split ('/\=/', $argument);

        $argumentValue = null;
        $argumentName = $argumentValuePair [0];

        if (count ($argumentValuePair) >= 2) {
          $argumentValue = $argumentValuePair [1];
        }

        $arguments [$argumentName] = $argumentValue;
      }

      return $arguments;
    }
  }}
}
