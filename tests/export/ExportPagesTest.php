<?php

require_once(WMP_PLUGIN_PATH."export/class-export.php");

class ExportPagesTest extends WP_UnitTestCase
{

    function setUp(){
        parent::setUp();

        update_option('wmpack_inactive_pages', array());
    }

    /**
     * Calling export_pages() with password protected pages returns empty
     */
    function test_export_pages_with_password_protected_pages_returns_empty()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_password' => '123123',
                'post_type' => 'page'
            )
        );

        $export = new WMobilePack_Export();
        $this->assertEquals($export->export_pages(), json_encode(array('pages' => array())));

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with draft pages returns empty
     */
    function test_export_pages_with_draft_pages_returns_empty()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_status' => 'draft',
                'post_type' => 'page'
            )
        );

        $export = new WMobilePack_Export();
        $this->assertEquals($export->export_pages(), json_encode(array('pages' => array())));

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with inactive pages returns empty
     */
    function test_export_pages_with_inactive_pages_returns_empty()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page'
            )
        );

        update_option('wmpack_inactive_pages', array($post_id));

        $export = new WMobilePack_Export();
        $this->assertEquals($export->export_pages(), json_encode(array('pages' => array())));

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() without ordered pages returns data with alphabetically ordered pages
     */
    function test_export_pages_without_ordered_pages_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'a',
                'post_content' => 'test content'
            )
        );

        $post_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'b',
                'post_content' => 'test content'
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(2, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('a', $data['pages'][0]['title']);
        $this->assertEquals(home_url().'/?page_id='.$post_id, $data['pages'][0]['link']);
        $this->assertEquals('', $data['pages'][0]['content']);

        $this->assertEquals($post_id2, $data['pages'][1]['id']);
        $this->assertEquals(2, $data['pages'][1]['order']);
        $this->assertEquals('b', $data['pages'][1]['title']);
        $this->assertEquals(home_url().'/?page_id='.$post_id2, $data['pages'][1]['link']);
        $this->assertEquals('', $data['pages'][1]['content']);

        wp_delete_post($post_id);
        wp_delete_post($post_id2);
    }

    /**
     * Calling export_pages() with ordered pages returns data
     */
    function test_export_pages_with_ordered_pages_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'a',
                'post_content' => 'test content',

            )
        );

        $post_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'b',
                'post_content' => 'test content'
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(2, count($data['pages']));
        $this->assertEquals($post_id2, $data['pages'][0]['id']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('b', $data['pages'][0]['title']);
        $this->assertEquals(home_url().'/?page_id='.$post_id2, $data['pages'][0]['link']);
        $this->assertEquals('', $data['pages'][0]['content']);

        $this->assertEquals($post_id, $data['pages'][1]['id']);
        $this->assertEquals(2, $data['pages'][1]['order']);
        $this->assertEquals('a', $data['pages'][1]['title']);
        $this->assertEquals(home_url().'/?page_id='.$post_id, $data['pages'][1]['link']);
        $this->assertEquals('', $data['pages'][1]['content']);

        wp_delete_post($post_id);
        wp_delete_post($post_id2);
    }


    /**
     * Calling export_pages() with pages that have images returns data
     */
    function test_export_pages_with_images_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => 'test content'
            )
        );

        // mock an attachment image and link it to the post
        $filename = "test_image.jpg";
        $wp_upload_dir = wp_upload_dir();

        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
            'post_mime_type' => 'image/jpeg',
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
        add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );
        wp_update_attachment_metadata( $attach_id, array('width' => 100, 'height' => 100));

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(home_url().'/?page_id='.$post_id, $data['pages'][0]['link']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        // check image
        $this->assertArrayHasKey('image', $data['pages'][0]);
        $this->assertEquals($wp_upload_dir['baseurl'] . '/'.$filename, $data['pages'][0]['image']['src']);
        $this->assertTrue(is_numeric($data['pages'][0]['image']['width']));
        $this->assertTrue(is_numeric($data['pages'][0]['image']['height']));

        wp_delete_post($post_id);
    }


    /**
     * Calling export_pages() with pages that have parents returns data
     */
    function test_export_pages_with_parent_id_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => 'test content',
                'post_parent' => 0
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(home_url().'/?page_id='.$post_id, $data['pages'][0]['link']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with ordered pages with children and special theme returns data
     *
     * @todo Refactor after submenu support is added to themes 1 and 4
     */
    function test_export_pages_with_ordered_pages_and_children_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'a',
                'post_content' => 'test content'
            )
        );

        $post_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'b',
                'post_content' => 'test content'
            )
        );

        $post_id3 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'c',
                'post_content' => 'child of page b',
                'post_parent' => $post_id2
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(3, count($data['pages']));
        $this->assertEquals($post_id2, $data['pages'][0]['id']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);
        $this->assertEquals('b', $data['pages'][0]['title']);
        $this->assertEquals('', $data['pages'][0]['content']);

        $this->assertEquals($post_id3, $data['pages'][1]['id']);
        $this->assertEquals(2, $data['pages'][1]['order']);
        $this->assertEquals($post_id2, $data['pages'][1]['parent_id']);
        $this->assertEquals('c', $data['pages'][1]['title']);
        $this->assertEquals('', $data['pages'][1]['content']);

        $this->assertEquals($post_id, $data['pages'][2]['id']);
        $this->assertEquals(3, $data['pages'][2]['order']);
        $this->assertEquals(0, $data['pages'][2]['parent_id']);
        $this->assertEquals('a', $data['pages'][2]['title']);
        $this->assertEquals('', $data['pages'][2]['content']);

        wp_delete_post($post_id);
        wp_delete_post($post_id2);
        wp_delete_post($post_id3);
    }

    /**
     * Calling export_pages() with ordered pages and password protected children returns data
     */
    function test_export_pages_with_ordered_pages_and_password_protected_children_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'a',
                'post_content' => 'test content'
            )
        );

        $post_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'b',
                'post_content' => 'test content'
            )
        );

        $post_id3 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'c',
                'post_content' => 'password protected child of page b',
                'post_password' => '123123',
                'post_parent' => $post_id2
            )
        );

        $post_id4 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'd',
                'post_content' => 'child of page b',
                'post_parent' => $post_id2
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(3, count($data['pages']));
        $this->assertEquals($post_id2, $data['pages'][0]['id']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);
        $this->assertEquals('b', $data['pages'][0]['title']);
        $this->assertEquals('', $data['pages'][0]['content']);

        // order=2 is skipped because page 'c' is password protected
        // even is page 'a' appears first in the array, its order is higher than page 'd'
        $this->assertEquals($post_id, $data['pages'][1]['id']);
        $this->assertEquals(4, $data['pages'][1]['order']);
        $this->assertEquals(0, $data['pages'][1]['parent_id']);
        $this->assertEquals('a', $data['pages'][1]['title']);
        $this->assertEquals('', $data['pages'][1]['content']);

        $this->assertEquals($post_id4, $data['pages'][2]['id']);
        $this->assertEquals(3, $data['pages'][2]['order']);
        $this->assertEquals($post_id2, $data['pages'][2]['parent_id']);
        $this->assertEquals('d', $data['pages'][2]['title']);
        $this->assertEquals('', $data['pages'][2]['content']);

        wp_delete_post($post_id);
        wp_delete_post($post_id2);
        wp_delete_post($post_id3);
        wp_delete_post($post_id4);
    }

    /**
     * Calling export_pages() with pages that don't have content returns data
     */
    function test_export_pages_with_no_content_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => ''
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(0, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with pages that have modified content returns data
     */
    function test_export_pages_with_modified_content_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => 'This is the original content'
            )
        );

        update_option('wmpack_page_'.$post_id, 'This is the modified content');

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        wp_delete_post($post_id);
    }

    /**
     *
     * Calling export_pages() with hidden root pages returns empty
     *
     */
    function test_export_pages_with_hidden_root_parent_returns_empty()
    {
        $parent_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'a',
                'post_content' => 'test content'
            )
        );

        $child_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'b',
                'post_content' => 'test content',
                'post_parent' => $parent_page_id
            )
        );

        update_option(WMobilePack_Options::$prefix.'inactive_pages', array($parent_page_id));

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertEquals(array('pages' => array()), $data);

        wp_delete_post($parent_page_id);
        wp_delete_post($child_page_id);
    }

    /**
     *
     * Calling export_pages() with hidden parent page does not return child pages
     *
     */
    function test_export_pages_with_hidden_parent_page_returns_data_without_children()
    {
        $parent_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'visible root page',
                'post_content' => 'test content'
            )
        );

        $child_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'visible child page',
                'post_content' => 'test content',
                'post_parent' => $parent_page_id
            )
        );

        $child_page_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 3,
                'post_title' => 'hidden child page',
                'post_content' => 'test content',
                'post_parent' => $parent_page_id
            )
        );

        // add child for the hidden page
        $grandchild_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 4,
                'post_title' => 'visible grandchild page',
                'post_content' => 'test content',
                'post_parent' => $child_page_id2
            )
        );

        update_option(WMobilePack_Options::$prefix.'inactive_pages', array($child_page_id2));

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(2, count($data['pages']));

        $this->assertEquals($parent_page_id, $data['pages'][0]['id']);
        $this->assertEquals('visible root page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        $this->assertEquals($child_page_id, $data['pages'][1]['id']);
        $this->assertEquals('visible child page', $data['pages'][1]['title']);
        $this->assertEquals(2, $data['pages'][1]['order']);
        $this->assertEquals('', $data['pages'][1]['content']);
        $this->assertEquals(1, $data['pages'][1]['has_content']);
        $this->assertEquals($parent_page_id, $data['pages'][1]['parent_id']);

        wp_delete_post($parent_page_id);
        wp_delete_post($child_page_id);
        wp_delete_post($child_page_id2);
        wp_delete_post($grandchild_page_id);
    }

    /**
     *
     * Calling export_pages() with hidden root page does not return child pages
     *
     */
    function test_export_pages_with_hidden_root_page_returns_data_without_children()
    {
        $parent_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'hidden root page',
                'post_content' => 'test content'
            )
        );

        $child_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'visible child page',
                'post_content' => 'test content',
                'post_parent' => $parent_page_id
            )
        );

        $grandchild_page_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 4,
                'post_title' => 'visible grandchild page',
                'post_content' => 'test content',
                'post_parent' => $child_page_id
            )
        );

        update_option(WMobilePack_Options::$prefix.'inactive_pages', array($parent_page_id));

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertEquals(array('pages' => array()), $data);

        wp_delete_post($parent_page_id);
        wp_delete_post($child_page_id);
        wp_delete_post($grandchild_page_id);
    }
}