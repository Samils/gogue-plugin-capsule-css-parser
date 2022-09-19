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
  use function php\requires;
  /**
   * Make sure the module base internal trait is not
   * declared in the php global scope defore creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\Base')) {
  /**
   * @trait Base
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
  trait Base {
    /**
     * comments reader
     */
    protected static $commentsReader;

    /**
     * block encoder store
     */
    protected $store;

    /**
     * @var array component datas
     */
    protected $componentDatas;

    /**
     * @method string|array run
     *
     * @param array $options options
     *  [
     *    :export => bool
     *  ]
     */
    public function run (array $options = []) {
      $commentsReader = requires ('gogue/code/comments-reader');

      self::$commentsReader = $commentsReader;

      $this->code = call_user_func_array ($commentsReader, [$this->code, [0]]);

      /**
       * Default file code
       */
      $codeInit = join ("\n", [
        "<?php \nnamespace App\View; \nuse Saml; \nuse Sami;\nuse Sammy\Packs\Samils\Capsule\CapsuleScopeContext; \nuse Sammy\Packs\CapsuleHelper; \nuse Sammy\Packs\CapsuleHelper\ArrayHelper; \nuse Sammy\Packs\CapsuleHelper\ObjectHelper;\n# Capsule Body\n\n\nif (!function_exists ('App\View\generateComponentSelectorRef')) {\nfunction generateComponentSelectorRef (\$componentName) {",
        "\treturn join ('', [",
          "\t\t\$componentName,",
          "\t\trand (0, 99999),",
          "\t\trand (111, 9999) * rand (222, 99999),",
          "\t\t(int)time () * rand (111, 99999),",
          "\t\ttime (),",
          "\t\t'r'",
          "\t]);",
        "}}\n\n"
      ]);

      $codeEnd = "\n\nif (!(is_object (\$module->exports) && \$module->exports instanceof Capsule)) {\n\t\$module->exports = Capsule::Create (function () {\n\t\treturn Capsule::PartialRender ('Fragment', []);\n\t});\n}";

      $blockEncoder = new BlockEncoder;

      $encoded = $blockEncoder->encodeBlocks ($this->code);

      $this->code = $encoded [0];
      $this->store = $encoded;

      /**
       * Read in the code '$this->code' whole the
       * styled component declarations for getting
       * the name, tagName and arguments from them.
       *
       * Send it to generate the php/capsule code for
       * the output.
       *
       *
       * todos:
       *  - Run the 'getComponentDeclarations' method
       *    to get the name, tagName and components
       *    signatures.
       *  - Run the 'getComponentDeclarationDatas' method
       *    for getting the name of the component and some
       *    other datas such as the arguments, the html
       *    tag to be generated and the type of selector
       *    for the generated html element returning the
       *    created selector for that tag associated to
       *    the declaration index.
       */
      $componentDeclarations = $this->getComponentDeclarations ($this->code);

      if (!$componentDeclarations) {
        return;
      }

      $componentDatas = $this->getComponentDeclarationDatas ($componentDeclarations ['declarations']);

      $this->componentDatas = $componentDatas;

      $importStatements = $this->getCodeImportStatements ($this->code);

      foreach ($importStatements as $filePath) {
        $fileContent = file_get_contents ($filePath);

        $fileParser = new static ($fileContent, $filePath);

        $fileExports = $fileParser->run (['exports' => true]);

        $this->componentDatas = array_merge (
          $this->componentDatas,
          $this->encodeBlocksStringsAndMergeStores ($fileExports)
        );
      }

      foreach ($componentDeclarations ['blocks'] as $i => $componentBlock) {

        $componentBlockBody = [];

        $componentParents = $this->componentDatas [$i]['componentParents'];

        if (count ($componentParents) >= 1) {
          foreach ($componentParents as $parentComponentName) {
            $parentComponentDatas = $this->getComponentByName ($parentComponentName);

            if ($parentComponentDatas && isset ($parentComponentDatas ['block'])) {

              # echo "\n\n\n\n\n\n Name => ", $parentComponentName, "\n\n\n\n\n\n";
              array_push ($componentBlockBody, $this->decodeBlockIfNotAndStripBlock ($parentComponentDatas ['block']));

              $this->componentDatas [$i]['componentArguments'] = array_merge ($parentComponentDatas ['componentArguments'], $this->componentDatas [$i]['componentArguments']);
            }
          }
        }

        array_push ($componentBlockBody, $this->decodeBlockIfNotAndStripBlock ($componentBlock));

        $this->componentDatas [$i]['block'] = join ('', [
          '{',
          join ("\n", $componentBlockBody),
          '}'
        ]);
      }

      $this->code = preg_replace (self::IMPORT_COMMAND_RE, '', $this->code);

      # print_r($this->componentDatas);
      # exit ("\n\n\n\n\nEXITZERO\n\n\n\n\n");
      # $options = ['exports' => true];
      # print_r($this->componentDatas);
      $exportComponentsContext = (boolean)(
        is_array ($options) &&
        isset ($options ['exports']) &&
        is_bool ($options ['exports']) &&
        $options ['exports']
      );

      if (!$exportComponentsContext) {
        $phpCapsuleCode = $this->generatePhpCapsuleCode ($this->componentDatas);

        return $commentsReader->decodeStrings (join ("", [
          $codeInit,
          $phpCapsuleCode,
          $codeEnd
        ]));
      }

      foreach ($this->componentDatas as $componentDataIndex => $componentData) {
        $this->componentDatas [$componentDataIndex] ['block'] = $this->decodeStringAndBlocks ($componentData ['block']);

        foreach ($componentData ['componentArguments'] as $key => $value) {
          $this->componentDatas [$componentDataIndex]['componentArguments'][$key] = $this->decodeStringAndBlocks ($value);
        }
      }

      return $this->componentDatas;
    }

    /**
     * @method string decodeIfNotAndStripBlock
     */
    private function decodeBlockIfNotAndStripBlock ($block) {
      $blockEncoder = new BlockEncoder;

      if (preg_match ('/^(::=block-block([0-9]+)::)$/i', trim ($block))) {
        $block = $blockEncoder->decodeBlock ($block, $this->store);
      }

      return preg_replace ('/^(\{\s*)/', '', preg_replace ('/(\s*\})$/', '', $block));
    }

    /**
     *
     */
    private function getComponentByName ($componentName) {
      if (!(is_array ($this->componentDatas) && $this->componentDatas)) {
        return null;
      }

      foreach ($this->componentDatas as $componentData) {
        if ($componentData ['componentName'] === $componentName) {
          return $componentData;
        }
      }
    }

    private function decodeStringAndBlocks ($code) {
      $blockEncoder = new BlockEncoder;

      return self::$commentsReader->decodeStrings ($blockEncoder->decodeBlocks ($code, $this->store));
    }

    private function encodeStringAndBlocks ($code) {
      $blockEncoder = new BlockEncoder;

      $code = call_user_func_array (self::$commentsReader, [$code, [0]]);

      $encoded = $blockEncoder->encodeBlocks ($code);
      $code = $encoded [0];

      $this->store ['store'] = array_merge ($this->store ['store'], $encoded ['store']);

      return $code;
    }

    /**
     * @method array encodeBlocksStringsAndMergeStores
     *
     */
    private function encodeBlocksStringsAndMergeStores ($componentDatas) {
      if (!(is_array ($componentDatas) && $componentDatas)) {
        return [];
      }

      $blockEncoder = new BlockEncoder;

      foreach ($componentDatas as $componentDataIndex => $componentData) {
        $componentDatas [$componentDataIndex]['block'] = $blockEncoder->decodeBlock ($this->encodeStringAndBlocks ($componentData ['block']), $this->store);


        foreach ($componentData ['componentArguments'] as $argumentKey => $argumentValue) {
          $componentDatas [$componentDataIndex]['componentArguments'][$argumentKey] = $this->encodeStringAndBlocks ($argumentValue);
        }
      }

      return $componentDatas;
    }

    public function getComponentDatas () {
      return $this->componentDatas;
    }
  }}
}
