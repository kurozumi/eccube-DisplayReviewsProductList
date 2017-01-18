<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2014 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *
 */

/**
 * プラグインの基底クラス
 *
 * @package Plugin
 * @author LOCKON CO.,LTD.
 * @version $Id: $
 */
class DisplayReviewsProductList extends SC_Plugin_Base
{
    /**
     * コンストラクタ
     *
     * @param  array $arrSelfInfo 自身のプラグイン情報
     * @return void
     */
    public function __construct(array $arrSelfInfo)
    {
        // プラグインを有効化したときの初期設定をココに追加する
        if($arrSelfInfo["enable"] == 1) {}

    }

    /**
     * インストール
     * installはプラグインのインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin plugin_infoを元にDBに登録されたプラグイン情報(dtb_plugin)
     * @return void
     */
    public function install($arrPlugin, $objPluginInstaller = null)
    {
        // htmlディレクトリにファイルを配置。
        $src_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/html/";
        $dest_dir = HTML_REALDIR;
        SC_Utils::copyDirectory($src_dir, $dest_dir);
        
        // テンプレートを配置。
        $src_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/data/Smarty/templates/";
        $dest_dir = SMARTY_TEMPLATES_REALDIR;
        SC_Utils::copyDirectory($src_dir, $dest_dir);
    }

    /**
     * アンインストール
     * uninstallはアンインストール時に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function uninstall($arrPlugin, $objPluginInstaller = null)
    {
        // htmlディレクトリのファイルを削除。
        $target_dir = HTML_REALDIR;
        $source_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/html/";
        self::deleteDirectory($target_dir, $source_dir);
        
        // テンプレートを削除。 
        $target_dir = SMARTY_TEMPLATES_REALDIR;
        $source_dir = PLUGIN_UPLOAD_REALDIR . "{$arrPlugin["plugin_code"]}/data/Smarty/templates/";
        self::deleteDirectory($target_dir, $source_dir);
    }

    /**
     * 稼働
     * enableはプラグインを有効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function enable($arrPlugin, $objPluginInstaller = null)
    {
    }

    /**
     * 停止
     * disableはプラグインを無効にした際に実行されます.
     * 引数にはdtb_pluginのプラグイン情報が渡されます.
     *
     * @param  array $arrPlugin プラグイン情報の連想配列(dtb_plugin)
     * @return void
     */
    public function disable($arrPlugin, $objPluginInstaller = null)
    {
    }

    /**
     * プラグインヘルパーへ, コールバックメソッドを登録します.
     *
     * @param integer $priority
     */
    public function register(SC_Helper_Plugin $objHelperPlugin, $priority)
    {
        $objHelperPlugin->addAction("prefilterTransform", array(&$this, "prefilterTransform"), $priority);
        $objHelperPlugin->addAction("LC_Page_Products_List_action_after", array(&$this, "products_list_action_after"), $priority);
        
    }
    
    /**
     * @param LC_Page_Admin_Products_Product $objPage 商品管理のページクラス
     * @return void
     */
    function products_list_action_after(LC_Page_EX $objPage)
    {
        // レビュー数を設定
        $objPage->arrProductReviews = $this->getProductRevirews($objPage->arrProducts);

    }
    
    public function getProductRevirews($productIds)
    {
        if (empty($productIds)) {
            return array();
        }
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $cols = 'product_id, count(product_id)';
        $from = 'dtb_rview';
        $where = 'product_id IN (' . SC_Utils_Ex::repeatStrWithSeparator('?', count($productIds)) . ')';
        $objQuery->setGroupBy("product_id");
        $productStatus = $objQuery->select($cols, $from, $where, $productIds);
        $results = array();
        /**
        foreach ($productStatus as $status) {
            $results[$status['product_id']][] = $status['product_status_id'];
        }
         * 
         */

        return $results;
    }

    /**
     * テンプレートをフックする
     *
     * @param string &$source
     * @param LC_Page_Ex $objPage
     * @param string $filename
     * @return void
     */
    public function prefilterTransform(&$source, LC_Page_Ex $objPage, $filename)
    {
        $objTransform = new SC_Helper_Transform($source);
        switch ($objPage->arrPageLayout['device_type_id']) {
            case DEVICE_TYPE_PC:
                break;
            case DEVICE_TYPE_MOBILE:
                break;
            case DEVICE_TYPE_SMARTPHONE:
                break;
            case DEVICE_TYPE_ADMIN:
            default:
                break;
        }
        $source = $objTransform->getHTML();

    }
    
    /**
     * 指定されたパスを比較して再帰的に削除します。
     * 
     * @param string $target_dir 削除対象のディレクトリ
     * @param string $source_dir 比較対象のディレクトリ
     */
    public static function deleteDirectory($target_dir, $source_dir) {
        $dir = opendir($source_dir);
        while ($name = readdir($dir)) {
            if ($name == '.' || $name == '..') {
                continue;
            }

            $target_path = $target_dir . '/' . $name;
            $source_path = $source_dir . '/' . $name;
            
            if (is_file($source_path)) {
                if (is_file($target_path)) {
                    unlink($target_path);
                    GC_Utils::gfPrintLog("$target_path を削除しました。");
                }
            } elseif (is_dir($source_path)) {
                if (is_dir($target_path)) {
                    self::deleteDirectory($target_path, $source_path);
                }
            }
        }
        closedir($dir);
    }

}
