<?php

namespace App\Support;

/**
 * Canonical advertisement types, pages, positions, and banner size recommendations.
 */
final class AdvertisementCatalog
{
    public const TYPE_BANNER = 'banner';

    public const TYPE_IFRAME = 'iframe';

    public const TYPE_HTML = 'html';

    public const SOURCE_GOOGLE = 'google';

    public const SOURCE_CUSTOM = 'custom';

    /**
     * Positions that accept multiple advertisements.
     *
     * @var list<string>
     */
    public const MULTI_POSITIONS = [
        'left_sidebar',
        'right_sidebar',
        'between_sections',
        'below_items',
        'after_header',
        'after_hero',
        'after_stats',
        'after_featured_exams',
        'after_questions',
        'after_categories',
        'after_blogs',
        'after_news',
        'after_testimonials',
        'after_faqs',
        'after_newsletter',
        'after_cta',
        'after_filters',
        'after_content',
        'above_footer',
        'below_content',
        'before_content',
        'after_about',
        'after_details',
        'after_related',
        'after_results',
    ];

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_BANNER => 'Banner Image',
            self::TYPE_IFRAME => 'Iframe URL',
            self::TYPE_HTML => 'HTML Code',
        ];
    }

    /**
     * @return array<string, array{label: string, width: int, height: int, note: string}>
     */
    public static function bannerSizes(): array
    {
        return [
            'leaderboard' => [
                'label' => 'Leaderboard',
                'width' => 728,
                'height' => 90,
                'note' => 'Best above the page title or above the footer on desktop detail and listing pages.',
            ],
            'large_leaderboard' => [
                'label' => 'Large Leaderboard',
                'width' => 970,
                'height' => 90,
                'note' => 'Wide desktop headers and premium top-of-page placements.',
            ],
            'medium_rectangle' => [
                'label' => 'Medium Rectangle',
                'width' => 300,
                'height' => 250,
                'note' => 'Ideal for sidebars and between content sections.',
            ],
            'large_rectangle' => [
                'label' => 'Large Rectangle',
                'width' => 336,
                'height' => 280,
                'note' => 'High-visibility in-content or sidebar placements.',
            ],
            'half_page' => [
                'label' => 'Half Page',
                'width' => 300,
                'height' => 600,
                'note' => 'Tall sidebars on long-form detail pages.',
            ],
            'wide_skyscraper' => [
                'label' => 'Wide Skyscraper',
                'width' => 160,
                'height' => 600,
                'note' => 'Narrow left/right sidebars on desktop layouts.',
            ],
            'mobile_banner' => [
                'label' => 'Mobile Banner',
                'width' => 320,
                'height' => 100,
                'note' => 'Recommended for mobile-first placements above or below titles.',
            ],
            'large_mobile_banner' => [
                'label' => 'Large Mobile Banner',
                'width' => 320,
                'height' => 50,
                'note' => 'Compact sticky-friendly mobile banners.',
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function homeLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Hero', 'skeleton' => 'hero', 'after' => 'after_hero'],
            ['id' => 'stats', 'label' => 'Stats', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'featured_exams', 'label' => 'Featured exams', 'skeleton' => 'cards4', 'after' => 'after_featured_exams'],
            ['id' => 'questions', 'label' => 'Questions', 'skeleton' => 'cards3', 'after' => 'after_questions'],
            ['id' => 'categories', 'label' => 'Categories', 'skeleton' => 'chips', 'after' => 'after_categories'],
            ['id' => 'blogs', 'label' => 'Blogs', 'skeleton' => 'cards3', 'after' => 'after_blogs'],
            ['id' => 'news', 'label' => 'News', 'skeleton' => 'cards3', 'after' => 'after_news'],
            ['id' => 'testimonials', 'label' => 'Testimonials', 'skeleton' => 'quotes', 'after' => 'after_testimonials'],
            ['id' => 'faqs', 'label' => 'FAQs', 'skeleton' => 'faq', 'after' => 'after_faqs'],
            ['id' => 'newsletter', 'label' => 'Newsletter', 'skeleton' => 'newsletter', 'after' => 'after_newsletter'],
            ['id' => 'cta', 'label' => 'Call to action', 'skeleton' => 'cta', 'after' => 'after_cta'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'after' => null, 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function listingLayout(string $heroLabel = 'Listing'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => $heroLabel, 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'filters', 'label' => 'Filters', 'skeleton' => 'filters', 'after' => 'after_filters'],
            ['id' => 'items', 'label' => 'Results grid', 'skeleton' => 'cards3', 'after' => 'below_items'],
            ['id' => 'load_more', 'label' => 'Load more', 'skeleton' => 'load_more', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function articleLayout(string $title = 'Article'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'title', 'label' => $title, 'skeleton' => 'article_title', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'banner', 'label' => 'Featured image', 'skeleton' => 'banner', 'after' => 'before_content'],
            ['id' => 'prose', 'label' => 'Article body', 'skeleton' => 'prose', 'after' => 'between_sections'],
            ['id' => 'related', 'label' => 'Related content', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Blog detail mirrors live show: article + related, then newsletter and CTA bands.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function blogArticleLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'title', 'label' => 'Blog article', 'skeleton' => 'article_title', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'banner', 'label' => 'Featured image', 'skeleton' => 'banner', 'after' => 'before_content'],
            ['id' => 'prose', 'label' => 'Article body', 'skeleton' => 'prose', 'after' => 'between_sections'],
            ['id' => 'related', 'label' => 'Related blogs', 'skeleton' => 'cards3', 'after' => 'after_related'],
            ['id' => 'newsletter', 'label' => 'Newsletter', 'skeleton' => 'newsletter', 'after' => 'after_newsletter'],
            ['id' => 'cta', 'label' => 'Call to action', 'skeleton' => 'cta', 'after' => 'after_cta'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Exam title', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'stats', 'label' => 'Exam stats', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'about', 'label' => 'About exam', 'skeleton' => 'section', 'after' => 'after_about'],
            ['id' => 'details', 'label' => 'Exam details', 'skeleton' => 'section', 'after' => 'after_details'],
            ['id' => 'cta', 'label' => 'Start exam CTA', 'skeleton' => 'cta', 'after' => 'after_cta'],
            ['id' => 'feedback', 'label' => 'Feedback', 'skeleton' => 'section', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function questionDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'top', 'label' => 'Question header', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'panel', 'label' => 'Question panel', 'skeleton' => 'prose', 'after' => 'before_content'],
            ['id' => 'options', 'label' => 'Options', 'skeleton' => 'faq', 'after' => 'between_sections'],
            ['id' => 'related', 'label' => 'Related blogs', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function categoryDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Category', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'exams', 'label' => 'Exams', 'skeleton' => 'cards4', 'after' => 'between_sections'],
            ['id' => 'subcats', 'label' => 'Subcategories', 'skeleton' => 'chips', 'after' => 'after_categories'],
            ['id' => 'blogs', 'label' => 'Related blogs', 'skeleton' => 'cards3', 'after' => 'after_blogs'],
            ['id' => 'news', 'label' => 'Related news', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function authorDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Author profile', 'skeleton' => 'author_hero', 'after' => 'below_title'],
            ['id' => 'exams', 'label' => 'Exams', 'skeleton' => 'cards4', 'after' => 'between_sections'],
            ['id' => 'blogs', 'label' => 'Blogs', 'skeleton' => 'cards3', 'after' => 'after_blogs'],
            ['id' => 'news', 'label' => 'News', 'skeleton' => 'cards3', 'after' => 'after_news'],
            ['id' => 'questions', 'label' => 'Questions', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examAttemptLayout(): array
    {
        return [
            ['id' => 'topbar', 'label' => 'Exam top bar', 'skeleton' => 'exam_topbar', 'after' => 'above_title'],
            ['id' => 'question', 'label' => 'Question area', 'skeleton' => 'exam_question', 'after' => 'below_content'],
            ['id' => 'rail', 'label' => 'Exam panel (before Final submit)', 'skeleton' => 'exam_rail', 'after' => 'before_final_submit'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examResultLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Exam result', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'summary', 'label' => 'Score summary', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'details', 'label' => 'Result details', 'skeleton' => 'section', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Exam rules & verification — matches frontend/candidate/exams/rules.blade.php
     * (hero → stats → warning → summary → instructions → rules → agree/CTA).
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examRulesLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Exam rules & verification', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'stats', 'label' => 'Exam stats', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'warning', 'label' => 'Warnings callout', 'skeleton' => 'section', 'after' => 'after_about'],
            ['id' => 'summary', 'label' => 'Assessment summary', 'skeleton' => 'section', 'after' => 'after_details'],
            ['id' => 'instructions', 'label' => 'Instructions', 'skeleton' => 'prose', 'after' => 'between_sections'],
            ['id' => 'rules', 'label' => 'Exam rules', 'skeleton' => 'faq', 'after' => 'after_content'],
            ['id' => 'actions', 'label' => 'Agree & continue', 'skeleton' => 'cta', 'after' => 'after_cta'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Exam prepare / verification checklist — candidate exam shell (no site header/footer).
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examPrepareLayout(): array
    {
        return [
            ['id' => 'hero', 'label' => 'Exam readiness', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'checklist', 'label' => 'Verification checklist', 'skeleton' => 'faq', 'after' => 'between_sections'],
            ['id' => 'actions', 'label' => 'Start actions', 'skeleton' => 'cta', 'after' => 'after_cta'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function faqsLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'FAQs', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'groups', 'label' => 'FAQ accordion', 'skeleton' => 'faq', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function searchLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Search', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'results', 'label' => 'Search results', 'skeleton' => 'cards3', 'after' => 'after_results'],
            ['id' => 'more', 'label' => 'More results', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function simpleLayout(string $title = 'Page'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => $title, 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'body', 'label' => 'Page content', 'skeleton' => 'prose', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function accountLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'toolbar', 'label' => 'Account toolbar', 'skeleton' => 'page_hero', 'after' => 'below_title'],
            ['id' => 'content', 'label' => 'Account content', 'skeleton' => 'section', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function errorLayout(string $code = '404'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'error', 'label' => 'Error '.$code, 'skeleton' => 'error', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'actions', 'label' => 'Actions', 'skeleton' => 'cta', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @param  list<array{id: string, label: string, skeleton: string, after?: ?string, before?: ?string, chrome?: bool}>  $blocks
     * @param  list<string>  $sidebars
     * @return list<string>
     */
    public static function positionsFromBlocks(array $blocks, array $sidebars = []): array
    {
        $keys = [];
        foreach ($blocks as $block) {
            if (! empty($block['before'])) {
                $keys[] = $block['before'];
            }
            if (! empty($block['after'])) {
                $keys[] = $block['after'];
            }
        }
        if (in_array('left', $sidebars, true)) {
            $keys[] = 'left_sidebar';
        }
        if (in_array('right', $sidebars, true)) {
            $keys[] = 'right_sidebar';
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<array{id: string, label: string, skeleton: string, after?: ?string, before?: ?string, chrome?: bool}>  $blocks
     * @param  list<string>  $sidebars
     * @return array{label: string, group: string, layout: string, description: string, sidebars: list<string>, layout_blocks: list<array>, positions: list<string>}
     */
    public static function definePage(
        string $label,
        string $group,
        string $layout,
        string $description,
        array $blocks,
        array $sidebars = []
    ): array {
        return [
            'label' => $label,
            'group' => $group,
            'layout' => $layout,
            'description' => $description,
            'sidebars' => $sidebars,
            'layout_blocks' => $blocks,
            'positions' => self::positionsFromBlocks($blocks, $sidebars),
        ];
    }

    /**
     * @return list<string>
     */
    public static function homePositionKeys(): array
    {
        return self::positionsFromBlocks(self::homeLayout(), ['left', 'right']);
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     group: string,
     *     layout: string,
     *     description: string,
     *     sidebars: list<string>,
     *     layout_blocks: list<array>,
     *     positions: list<string>
     * }>
     */
    public static function pages(): array
    {
        // Desktop shell: .et-container is max ~1160px centered, leaving empty L/R gutters
        // (same as Exam List red zones). Those gutters are ad rails — not Blade "sidebar" components.
        $both = ['left', 'right'];
        $none = [];

        return [
            'home' => self::definePage(
                'Home',
                'Core',
                'home',
                'Public homepage — navbar through footer, matching real section order.',
                self::homeLayout(),
                $both
            ),
            'exam_list' => self::definePage('Exams — List', 'Exams', 'listing', 'Exam listing / catalog page with left and right gutter ad rails.', self::listingLayout('Exams'), $both),
            'exam_detail' => self::definePage('Exams — Detail', 'Exams', 'detail', 'Single exam overview with left/right gutter rails.', self::examDetailLayout(), $both),
            'exam_rules' => self::definePage('Exams — Rules', 'Exams', 'detail', 'Exam rules & verification page before starting.', self::examRulesLayout(), $both),
            'exam_prepare' => self::definePage('Exams — Prepare', 'Exams', 'detail', 'Device/verification checklist before the live attempt (candidate shell).', self::examPrepareLayout(), $both),
            'exam_attempt' => self::definePage('Exams — Live Attempt', 'Exams', 'exam_attempt', 'In-progress exam runner (no site header/footer).', self::examAttemptLayout(), $both),
            'exam_result' => self::definePage('Exams — Result', 'Exams', 'detail', 'Exam result summary page with left/right gutter rails.', self::examResultLayout(), $both),
            'question_list' => self::definePage('Questions — List', 'Questions', 'listing', 'Question listing page with left/right gutter rails.', self::listingLayout('Questions'), $both),
            'question_categories' => self::definePage('Questions — Categories', 'Questions', 'listing', 'Question category index with left/right gutter rails.', self::listingLayout('Question categories'), $both),
            'question_detail' => self::definePage('Questions — Detail', 'Questions', 'detail', 'Single question page with left/right gutter rails.', self::questionDetailLayout(), $both),
            'blog_list' => self::definePage('Blogs — List', 'Blogs', 'listing', 'Blog listing page with left/right gutter rails.', self::listingLayout('Blogs'), $both),
            'blog_detail' => self::definePage('Blogs — Detail', 'Blogs', 'article', 'Blog article with related rail, newsletter, CTA, and gutter ads.', self::blogArticleLayout(), $both),
            'news_list' => self::definePage('News — List', 'News', 'listing', 'News listing page with left/right gutter rails.', self::listingLayout('News'), $both),
            'news_detail' => self::definePage('News — Detail', 'News', 'article', 'News article page with left/right gutter rails.', self::articleLayout('News article'), $both),
            'categories' => self::definePage('Categories', 'Browse', 'listing', 'Category browse page with left/right gutter rails.', self::listingLayout('Categories'), $both),
            'category_detail' => self::definePage('Category — Detail', 'Browse', 'detail', 'Category page with related content and gutter rails.', self::categoryDetailLayout(), $both),
            'authors' => self::definePage('Authors', 'Browse', 'listing', 'Authors listing page with left/right gutter rails.', self::listingLayout('Authors'), $both),
            'author_detail' => self::definePage('Author — Detail', 'Browse', 'detail', 'Author profile with left/right gutter rails.', self::authorDetailLayout(), $both),
            'faqs' => self::definePage('FAQs', 'Pages', 'simple', 'FAQ page with left/right gutter rails.', self::faqsLayout(), $both),
            'search' => self::definePage('Search', 'Pages', 'listing', 'Site search results with left/right gutter rails.', self::searchLayout(), $both),
            'sitemap' => self::definePage('Sitemap', 'Pages', 'simple', 'HTML sitemap with left/right gutter rails.', self::simpleLayout('Sitemap'), $both),
            'cms_page' => self::definePage('CMS Pages', 'Pages', 'simple', 'CMS pages with left/right gutter rails.', self::simpleLayout('CMS page'), $both),
            'account' => self::definePage('Account Area', 'Account', 'simple', 'Candidate account pages with left/right gutter rails.', self::accountLayout(), $both),
            'error_404' => self::definePage('Error 404', 'Errors', 'error', 'Page not found.', self::errorLayout('404'), $none),
            'error_403' => self::definePage('Error 403', 'Errors', 'error', 'Forbidden / access denied.', self::errorLayout('403'), $none),
            'error_419' => self::definePage('Error 419', 'Errors', 'error', 'Page expired / CSRF token mismatch.', self::errorLayout('419'), $none),
            'error_429' => self::definePage('Error 429', 'Errors', 'error', 'Too many requests.', self::errorLayout('429'), $none),
            'error_500' => self::definePage('Error 500', 'Errors', 'error', 'Server error.', self::errorLayout('500'), $none),
            'error_503' => self::definePage('Error 503', 'Errors', 'error', 'Service unavailable / maintenance.', self::errorLayout('503'), $none),
        ];
    }

    /**
     * @return array<string, array{label: string, note: string, multi: bool}>
     */
    public static function positions(): array
    {
        $multi = array_flip(self::MULTI_POSITIONS);

        $defs = [
            'above_title' => ['Above page title', 'Shows directly above the main H1 / page heading.'],
            'below_title' => ['Below page title', 'Shows under the heading, before primary content.'],
            'before_content' => ['Before content', 'Inserted before the main content block.'],
            'after_content' => ['After content', 'Inserted after the main content block.'],
            'between_sections' => ['Between sections', 'Between major content sections. Multiple ads allowed.'],
            'below_items' => ['Below every list item', 'After each card/row on listing pages. Multiple ads allowed.'],
            'below_content' => ['Below content', 'Under the primary interactive content area.'],
            'before_final_submit' => ['Before final submit', 'Exam panel slot between the question palette and Final submit.'],
            'left_sidebar' => ['Left sidebar', 'Left ads panel. Multiple ads allowed and stacked.'],
            'right_sidebar' => ['Right sidebar', 'Right ads panel. Multiple ads allowed and stacked.'],
            'above_footer' => ['Above footer', 'Full-width strip just above the site footer.'],
            'after_header' => ['After navbar', 'Between the site navbar and the first content section.'],
            'after_hero' => ['After hero', 'Directly below the homepage hero carousel.'],
            'after_stats' => ['After stats', 'Below the stats / social-proof section.'],
            'after_featured_exams' => ['After featured exams', 'Below the featured exams grid.'],
            'after_questions' => ['After questions', 'Below the questions section.'],
            'after_categories' => ['After categories', 'Below the categories section.'],
            'after_blogs' => ['After blogs', 'Below the blogs section.'],
            'after_news' => ['After news', 'Below the news section.'],
            'after_testimonials' => ['After testimonials', 'Below the testimonials section.'],
            'after_faqs' => ['After FAQs', 'Below the FAQs section.'],
            'after_newsletter' => ['After newsletter', 'Below the newsletter band.'],
            'after_cta' => ['After CTA', 'Below a call-to-action section.'],
            'after_filters' => ['After filters', 'Below the listing filter toolbar.'],
            'after_about' => ['After about', 'Below the about section on exam detail.'],
            'after_details' => ['After details', 'Below exam details / meta cards.'],
            'after_related' => ['After related', 'Below related content blocks.'],
            'after_results' => ['After results', 'Below the first search results group.'],
        ];

        $out = [];
        foreach ($defs as $key => [$label, $note]) {
            $out[$key] = [
                'label' => $label,
                'note' => $note,
                'multi' => isset($multi[$key]),
            ];
        }

        return $out;
    }

    public static function defaultPageKey(): string
    {
        return 'home';
    }

    public static function page(string $key): ?array
    {
        return self::pages()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function pageKeys(): array
    {
        return array_keys(self::pages());
    }

    /**
     * @return list<string>
     */
    public static function positionKeysForPage(string $pageKey): array
    {
        return self::page($pageKey)['positions'] ?? [];
    }

    public static function allowsMultiple(string $positionKey): bool
    {
        return in_array($positionKey, self::MULTI_POSITIONS, true);
    }

    /**
     * @return list<string>
     */
    public static function typeKeys(): array
    {
        return array_keys(self::types());
    }

    /**
     * @return list<string>
     */
    public static function bannerSizeKeys(): array
    {
        return array_keys(self::bannerSizes());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function pagesGrouped(): array
    {
        $grouped = [];
        foreach (self::pages() as $key => $page) {
            $grouped[$page['group']][$key] = $page['label'];
        }

        return $grouped;
    }
}
