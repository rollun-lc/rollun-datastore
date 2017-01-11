<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:35 PM
 */

namespace rollun\test\datastore\DataStore\Composite;

use Interop\Container\ContainerInterface;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\Composite\Composite;

abstract class AbstractCompositeTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Composite */
    protected $object;

    /** @var  ContainerInterface */
    protected $container;

    protected function unsetResultFiled(array $result, array $ides)
    {
        $newResult = [];

        foreach ($result as &$item) {
            if (in_array($item['id'], $ides)) {
                $newResult[] = $item;
            }
        }
        return $newResult;
    }

    abstract protected function initProduct();

    abstract protected function initImages();

    abstract protected function initCategory();

    abstract protected function initCategoryProducts();
    //simple query for tables (query without node)
    /*************************************************************************/

    public function test__query_images()
    {
        $this->initImages();
        $result = $this->object->query(new Query());
        $this->assertEquals([
            ["id" => "21", "image" => "icon1.jpg", "product_id" => "11"],
            ["id" => "22", "image" => "icon2.jpg", "product_id" => "12"],
            ["id" => "23", "image" => "icon3.jpg", "product_id" => "11"],
            ["id" => "24", "image" => "icon4.jpg", "product_id" => "13"],
            ["id" => "25", "image" => "icon5.jpg", "product_id" => "14"],
            ["id" => "26", "image" => "icon6.jpg", "product_id" => "12"],
            ["id" => "27", "image" => "icon6.jpg", "product_id" => "15"],
            ["id" => "28", "image" => "icon7.jpg", "product_id" => "14"],
            ["id" => "29", "image" => "icon7.jpg", "product_id" => "14"],
            ["id" => "30", "image" => "icon7.jpg", "product_id" => "15"],
            ["id" => "31", "image" => "icon8.jpg", "product_id" => "16"],
        ], $result);
    }

    public function test__query_product()
    {
        $this->initProduct();
        $result = $this->object->query(new Query());
        $this->assertEquals([
            ["id" => "11", "title" => "Edelweiss", "price" => "200"],
            ["id" => "12", "title" => "Rose", "price" => "50"],
            ["id" => "13", "title" => "Queen Rose 1", "price" => "150"],
            ["id" => "14", "title" => "Queen Violet", "price" => "120"],
            ["id" => "15", "title" => "King Rose 1", "price" => "200"],
            ["id" => "16", "title" => "King Rose 2", "price" => "250"],
        ], $result);
    }

    public function test__query_category()
    {
        $this->initCategory();
        $result = $this->object->query(new Query());
        $this->assertEquals([
            ["id" => "41", "title" => "Miscellaneous"],
            ["id" => "42", "title" => "Flower"],
            ["id" => "43", "title" => "Roses"],
            ["id" => "44", "title" => "King Rose"],
            ["id" => "45", "title" => "Queen Rose"],
        ], $result);
    }

    public function test__query_category_products()
    {
        $this->initCategoryProducts();
        $result = $this->object->query(new Query());
        $this->assertEquals([
            ["id" => "41", 'category_id' => '41', 'product_id' => '11'],
            ["id" => "42", 'category_id' => '42', 'product_id' => '12'],
            ["id" => "43", 'category_id' => '42', 'product_id' => '13'],
            ["id" => "44", 'category_id' => '42', 'product_id' => '14'],
            ["id" => "45", 'category_id' => '42', 'product_id' => '15'],
            ["id" => "46", 'category_id' => '42', 'product_id' => '16'],
            ["id" => "48", 'category_id' => '43', 'product_id' => '12'],
            ["id" => "49", 'category_id' => '43', 'product_id' => '13'],
            ["id" => "50", 'category_id' => '43', 'product_id' => '15'],
            ["id" => "51", 'category_id' => '43', 'product_id' => '16'],
            ["id" => "52", 'category_id' => '44', 'product_id' => '15'],
            ["id" => "53", 'category_id' => '44', 'product_id' => '16'],
            ["id" => "54", 'category_id' => '45', 'product_id' => '13'],
        ], $result);
    }

    //query for tables with select bounds
    /*************************************************************************/

    public function test_query_images_SelectProduct()
    {
        $this->initImages();
        $query = new Query();
        $query->setSelect(new SelectNode(['product.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [21, 22, 23, 24, 25]);

        $this->assertEquals([
            ["id" => "21", "image" => "icon1.jpg", "product_id" => "11", "product.title" => "Edelweiss", "product.price" => "200"],
            ["id" => "22", "image" => "icon2.jpg", "product_id" => "12", "product.title" => "Rose", "product.price" => "50"],
            ["id" => "23", "image" => "icon3.jpg", "product_id" => "11", "product.title" => "Edelweiss", "product.price" => "200"],
            ["id" => "24", "image" => "icon4.jpg", "product_id" => "13", "product.title" => "Queen Rose 1", "product.price" => "150"],
            ["id" => "25", "image" => "icon5.jpg", "product_id" => "14", "product.title" => "Queen Violet", "product.price" => "120"],
        ], $result);
    }

    public function test_query_product_SelectImage()
    {
        $this->initProduct();
        $query = new Query();
        $query->setSelect(new SelectNode(['images.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [11, 12, 13]);

        $this->assertEquals([

            ["id" => "11", "title" => "Edelweiss", "price" => "200", "images" => [
                ["id" => "21", "image" => "icon1.jpg", "product_id" => "11"],
                ["id" => "23", "image" => "icon3.jpg", "product_id" => "11"],
            ]],
            ["id" => "12", "title" => "Rose", "price" => "50", "images" => [
                ["id" => "22", "image" => "icon2.jpg", "product_id" => "12"],
                ["id" => "26", "image" => "icon6.jpg", "product_id" => "12"],
            ]],
            ["id" => "13", "title" => "Queen Rose 1", "price" => "150", "images" => [
                ["id" => "24", "image" => "icon4.jpg", "product_id" => "13"],
            ]]

        ], $result);
    }

    public function test_query_product_SelectCategoryProducts()
    {
        $this->initProduct();
        $query = new Query();
        $query->setSelect(new SelectNode(['category_products.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [11, 12, 13]);

        $this->assertEquals([

            ["id" => "11", "title" => "Edelweiss", "price" => "200", "category_products" => [
                ["id" => "41", 'category_id' => '41', 'product_id' => '11'],
            ]],
            ["id" => "12", "title" => "Rose", "price" => "50", "category_products" => [
                ["id" => "42", 'category_id' => '42', 'product_id' => '12'],
                ["id" => "48", 'category_id' => '43', 'product_id' => '12'],
            ]],
            ["id" => "13", "title" => "Queen Rose 1", "price" => "150", "category_products" => [
                ["id" => "43", 'category_id' => '42', 'product_id' => '13'],
                ["id" => "49", 'category_id' => '43', 'product_id' => '13'],
                ["id" => "54", 'category_id' => '45', 'product_id' => '13'],
            ]],

        ], $result);
    }

    public function test_query_category_SelectCategoryProducts()
    {
        $this->initCategory();
        $query = new Query();
        $query->setSelect(new SelectNode(['category_products.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [41, 42, 43]);

        $this->assertEquals([
            ["id" => "41", "title" => "Miscellaneous", "category_products" => [
                ["id" => "41", 'category_id' => '41', 'product_id' => '11'],
            ]],
            ["id" => "42", "title" => "Flower", "category_products" => [
                ["id" => "42", 'category_id' => '42', 'product_id' => '12'],
                ["id" => "43", 'category_id' => '42', 'product_id' => '13'],
                ["id" => "44", 'category_id' => '42', 'product_id' => '14'],
                ["id" => "45", 'category_id' => '42', 'product_id' => '15'],
                ["id" => "46", 'category_id' => '42', 'product_id' => '16'],
            ]],
            ["id" => "43", "title" => "Roses", "category_products" => [
                ["id" => "48", 'category_id' => '43', 'product_id' => '12'],
                ["id" => "49", 'category_id' => '43', 'product_id' => '13'],
                ["id" => "50", 'category_id' => '43', 'product_id' => '15'],
                ["id" => "51", 'category_id' => '43', 'product_id' => '16'],
            ]],
        ], $result);
    }

    public function test_query_category_products_SelectCategory()
    {
        $this->initCategoryProducts();
        $query = new Query();
        $query->setSelect(new SelectNode(['category.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [41, 42, 48, 53, 54]);

        $this->assertEquals([
            ["id" => "41", 'category_id' => '41', 'product_id' => '11', 'category.title' => "Miscellaneous"],
            ["id" => "42", 'category_id' => '42', 'product_id' => '12', 'category.title' => "Flower"],
            ["id" => "48", 'category_id' => '43', 'product_id' => '12', 'category.title' => "Roses"],
            ["id" => "53", 'category_id' => '44', 'product_id' => '16', 'category.title' => "King Rose"],
            ["id" => "54", 'category_id' => '45', 'product_id' => '13', 'category.title' => "Queen Rose"],
        ], $result);
    }

    public function test_query_category_products_SelectProduct()
    {
        $this->initCategoryProducts();
        $query = new Query();
        $query->setSelect(new SelectNode(['product.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [41, 42, 50, 53, 54]);

        $this->assertEquals([
            ["id" => "41", 'category_id' => '41', 'product_id' => '11', 'product.title' => "Edelweiss", "product.price" => "200"],
            ["id" => "42", 'category_id' => '42', 'product_id' => '12', 'product.title' => "Rose", "product.price" => "50"],
            ["id" => "50", 'category_id' => '43', 'product_id' => '15', 'product.title' => "King Rose 1", "product.price" => "200"],
            ["id" => "53", 'category_id' => '44', 'product_id' => '16', 'product.title' => "King Rose 2", "product.price" => "250"],
            ["id" => "54", 'category_id' => '45', 'product_id' => '13', 'product.title' => "Queen Rose 1", "product.price" => "150"],
        ], $result);
    }

    //query for tables with select bounds with bounds
    /*************************************************************************/

    public function test_query_images_SelectProductSharp()
    {
        $this->initImages();
        $query = new Query();
        $query->setSelect(new SelectNode(['product.#']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [21, 22, 23, 24]);

        $this->assertEquals([

            ["id" => "21", "image" => "icon1.jpg", "product_id" => "11", "product.title" => "Edelweiss", "product.price" => "200", "product.category_products" => [
                ["id" => "41", 'category_id' => '41', 'product_id' => '11'],
            ]],
            ["id" => "22", "image" => "icon2.jpg", "product_id" => "12", "product.title" => "Rose", "product.price" => "50", "product.category_products" => [
                ["id" => "42", 'category_id' => '42', 'product_id' => '12'],
                ["id" => "48", 'category_id' => '43', 'product_id' => '12'],
            ]],
            ["id" => "23", "image" => "icon3.jpg", "product_id" => "11", "product.title" => "Edelweiss", "product.price" => "200", "product.category_products" => [
                ["id" => "41", 'category_id' => '41', 'product_id' => '11'],
            ]],
            ["id" => "24", "image" => "icon4.jpg", "product_id" => "13", "product.title" => "Queen Rose 1", "product.price" => "150", "product.category_products" => [
                ["id" => "43", 'category_id' => '42', 'product_id' => '13'],
                ["id" => "49", 'category_id' => '43', 'product_id' => '13'],
                ["id" => "54", 'category_id' => '45', 'product_id' => '13'],
            ]],
        ], $result);
    }

    public function test_query_product_SelectImageSharp()
    {
        $this->initProduct();
        $query = new Query();
        $query->setSelect(new SelectNode(['images.#']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [11, 12, 13]);


        $this->assertEquals([

            ["id" => "11", "title" => "Edelweiss", "price" => "200", "images" => [
                ["id" => "21", "image" => "icon1.jpg", "product_id" => "11"],
                ["id" => "23", "image" => "icon3.jpg", "product_id" => "11"],
            ]],
            ["id" => "12", "title" => "Rose", "price" => "50", "images" => [
                ["id" => "22", "image" => "icon2.jpg", "product_id" => "12"],
                ["id" => "26", "image" => "icon6.jpg", "product_id" => "12"],
            ]],
            ["id" => "13", "title" => "Queen Rose 1", "price" => "150", "images" => [
                ["id" => "24", "image" => "icon4.jpg", "product_id" => "13"],
            ]],

        ], $result);
    }

    public function test_query_product_SelectCategoryProductSharp()
    {
        $this->initProduct();
        $query = new Query();
        $query->setSelect(new SelectNode(['category_products.#']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [11, 12]);

        $this->assertEquals([
            ["id" => "11", "title" => "Edelweiss", "price" => "200", "category_products" => [
                ["id" => "41", 'category_id' => '41', 'product_id' => '11', "category.title" => "Miscellaneous"],
            ]],
            ["id" => "12", "title" => "Rose", "price" => "50", "category_products" => [
                ["id" => "42", 'category_id' => '42', 'product_id' => '12', "category.title" => "Flower"],
                ["id" => "48", 'category_id' => '43', 'product_id' => '12', "category.title" => "Roses"],
            ]],
        ], $result);
    }

    public function test_query_category_SelectCategoryProductsSharp()
    {
        $this->initCategory();
        $query = new Query();
        $query->setSelect(new SelectNode(['category_products.#']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [41, 42]);

        $this->assertEquals([
            ["id" => "41", "title" => "Miscellaneous", "category_products" => [
                ["id" => "41", 'category_id' => '41', 'product_id' => '11', "product.title" => "Edelweiss", "product.price" => 200],
            ]],
            ["id" => "42", "title" => "Flower", "category_products" => [
                ["id" => "42", 'category_id' => '42', 'product_id' => '12', "product.title" => "Rose", "product.price" => "50"],
                ["id" => "43", 'category_id' => '42', 'product_id' => '13', "product.title" => "Queen Rose 1", "product.price" => "150"],
                ["id" => "44", 'category_id' => '42', 'product_id' => '14', "product.title" => "Queen Violet", "product.price" => "120"],
                ["id" => "45", 'category_id' => '42', 'product_id' => '15', "product.title" => "King Rose 1", "product.price" => "200"],
                ["id" => "46", 'category_id' => '42', 'product_id' => '16', "product.title" => "King Rose 2", "product.price" => "250"],
            ]],
        ], $result);
    }

    //query for tables with select meany to meany select(category., product.)
    /*************************************************************************/

    public function test_query_category_products_SelectProduct_Category()
    {
        $this->initCategoryProducts();
        $query = new Query();
        $query->setSelect(new SelectNode(['product.', 'category.']));
        $result = $this->object->query($query);

        $result = $this->unsetResultFiled($result, [41, 42, 50, 53, 54]);

        $this->assertEquals([
            ["id" => "41", 'category_id' => '41', 'product_id' => '11', 'product.title' => "Edelweiss", "product.price" => "200",
            "category.title" => "Miscellaneous"],
            ["id" => "42", 'category_id' => '42', 'product_id' => '12', 'product.title' => "Rose", "product.price" => "50",
            "category.title" => "Flower"],
            ["id" => "50", 'category_id' => '43', 'product_id' => '15', 'product.title' => "King Rose 1", "product.price" => "200",
            "category.title" => "Roses"],
            ["id" => "53", 'category_id' => '44', 'product_id' => '16', 'product.title' => "King Rose 2", "product.price" => "250",
            "category.title" => "King Rose"],
            ["id" => "54", 'category_id' => '45', 'product_id' => '13', 'product.title' => "Queen Rose 1", "product.price" => "150",
            "category.title" => "Queen Rose"],
        ], $result);
    }

    protected function setUp()
    {
        $this->container = include 'config/container.php';
        /*$installer = new Installer($this->container);
        $installer->addDataEavExampleStoreCatalog();*/
    }
}
