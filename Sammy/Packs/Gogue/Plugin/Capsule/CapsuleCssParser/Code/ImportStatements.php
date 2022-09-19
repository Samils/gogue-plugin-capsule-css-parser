<?php
/**
 * @version 2.0
 * @author Sammy
 *
 * @keywords Samils, ils, php framework
 * -----------------
 * @package Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\Data
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
namespace Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\Code {
  use Sammy\Packs\Gogue\Component\Code\BlockEncoder;
  /**
   * Make sure the module base internal trait is not
   * declared in the php global scope defore creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleCssParser\Code\ImportStatements')) {
  /**
   * @trait ImportStatements
   * Base internal trait for the
   * Gogue\Plugin\Capsule\CapsuleCssParser\Code module.
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
  trait ImportStatements {
    /**
     * @method boolean getCodeImportStatements
     */
    private function getCodeImportStatements ($partialCode) {

      $importCommandRe = self::IMPORT_COMMAND_RE;
      $blockEncoder = new BlockEncoder;

      preg_match_all ($importCommandRe, $partialCode, $importCommandMatches);

      $importStatements = [];

      if (count ($importCommandMatches [1]) >= 1) {
        foreach ($importCommandMatches [1] as $filePathRef) {
          $filePathRef = preg_replace ('/^url\s*/i', '', $filePathRef);

          $filePath = ($this->isStringRef ($filePathRef)) ? $this->stringRefToString ($filePathRef) : null;

          if (is_null ($filePath)) {
            $filePathWithDecodedBlocks = $blockEncoder->decodeBlocks ($filePathRef, $this->store);

            $filePath = preg_replace ('/(^\(|\)$)/', '', $filePathWithDecodedBlocks);
          }

          if (preg_match ('/^(\.\/)/', $filePath)) {
            $filePath = join (DIRECTORY_SEPARATOR, [
              dirname ($this->filePath),
              $filePath
            ]);
          }

          $realFilePath = realpath ($filePath);

          if (is_file ($filePath)) {
            array_push ($importStatements, $realFilePath);
          }
        }
      }

      return $importStatements;
    }

    /**
     * @method array getFileImportStatements
     */
    public function getFileImportStatements (string $filePath) {
      if (!(is_file ($filePath))) {
        return [];
      }

      $fileContent = file_get_contents ($filePath);

      $commentsReader = requires ('gogue/code/comments-reader');

      self::$commentsReader = $commentsReader;

      $fileContent = call_user_func_array ($commentsReader, [$fileContent, [0]]);

      $blockEncoder = new BlockEncoder;

      $encoded = $blockEncoder->encodeBlocks ($fileContent);

      $fileContent = $encoded [0];
      $this->store = $encoded;

      return $this->getCodeImportStatements ($fileContent);
    }
  }}
}
