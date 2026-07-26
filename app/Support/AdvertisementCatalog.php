<?php

namespace App\Support;

/**
 * Canonical advertisement types and placements for admin + frontend rendering.
 */
final class AdvertisementCatalog
{
    public const TYPE_BANNER = 'banner';

    public const TYPE_GOOGLE_ADS = 'google_ads';

    public const TYPE_CUSTOM_HTML = 'custom_html';

    public const TYPE_IFRAME = 'iframe';

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_BANNER => 'Banner image',
            self::TYPE_GOOGLE_ADS => 'Google AdSense',
            self::TYPE_CUSTOM_HTML => 'Custom HTML / JS',
            self::TYPE_IFRAME => 'Iframe',
        ];
    }

    /**
     * Placement groups for the visual admin preview.
     *
     * @return array<string, array{label: string, page: string, slots: array<string, string>}>
     */
    public static function placementGroups(): array
    {
        return [
            'blog_detail' => [
                'label' => 'Blog detail',
                'page' => 'Blog article page',
                'slots' => [
                    'blog_detail_above_h1' => 'Above H1',
                    'blog_detail_after_first_paragraph' => 'After first paragraph',
                    'blog_detail_between_sections' => 'Between content sections',
                    'blog_detail_before_comments' => 'Before comments / related',
                    'blog_detail_sidebar_top' => 'Sidebar top',
                    'blog_detail_sidebar_middle' => 'Sidebar middle',
                    'blog_detail_sidebar_bottom' => 'Sidebar bottom',
                ],
            ],
            'news_detail' => [
                'label' => 'News detail',
                'page' => 'News article page',
                'slots' => [
                    'news_detail_above_h1' => 'Above H1',
                    'news_detail_after_first_paragraph' => 'After first paragraph',
                    'news_detail_between_sections' => 'Between content sections',
                    'news_detail_before_comments' => 'Before comments / related',
                    'news_detail_sidebar_top' => 'Sidebar top',
                    'news_detail_sidebar_middle' => 'Sidebar middle',
                    'news_detail_sidebar_bottom' => 'Sidebar bottom',
                ],
            ],
            'question_list' => [
                'label' => 'Question list',
                'page' => 'Questions index',
                'slots' => [
                    'question_list_inline' => 'Inline every N questions',
                ],
            ],
            'exam_attempt' => [
                'label' => 'Exam attempt',
                'page' => 'Live exam runner & result',
                'slots' => [
                    'exam_attempt_left' => 'Left sidebar',
                    'exam_attempt_right' => 'Right sidebar',
                    'exam_attempt_bottom' => 'Bottom section',
                    'exam_result' => 'Result page',
                ],
            ],
            'lists_footer' => [
                'label' => 'Lists & global',
                'page' => 'Listing pages and footer',
                'slots' => [
                    'home_sidebar' => 'Home sidebar',
                    'exam_list' => 'Exam list',
                    'blog_list' => 'Blog list',
                    'news_list' => 'News list',
                    'footer' => 'Site footer',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function placements(): array
    {
        $all = [];
        foreach (self::placementGroups() as $group) {
            foreach ($group['slots'] as $key => $label) {
                $all[$key] = $group['label'].' — '.$label;
            }
        }

        return $all;
    }

    /**
     * @return list<string>
     */
    public static function placementKeys(): array
    {
        return array_keys(self::placements());
    }
}
