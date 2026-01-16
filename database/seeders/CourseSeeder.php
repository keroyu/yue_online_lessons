<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::create([
            'name' => '經營者思維入門',
            'tagline' => '從員工心態轉換到經營者思維的第一步',
            'description' => '這門講座將帶你了解經營者與員工思維的根本差異。透過實際案例分析，你將學會如何用經營者的視角看待問題，做出更有價值的決策。適合想要創業或在職場上突破的你。',
            'price' => 990,
            'thumbnail' => '/images/courses/course-1.jpg',
            'instructor_name' => 'Yue Yu',
            'type' => 'lecture',
            'is_published' => true,
            'sort_order' => 1,
            'portaly_url' => 'https://portaly.cc/yueyuknows/products/1',
            'portaly_product_id' => 'prod_001',
        ]);

        Course::create([
            'name' => '時間管理的藝術',
            'tagline' => '讓你每天多出兩小時的高效工作術',
            'description' => '你是否總覺得時間不夠用？這門迷你課程將教你科學的時間管理方法，包含番茄工作法進階應用、任務優先級矩陣、以及如何建立可持續的高效習慣。',
            'price' => 1990,
            'thumbnail' => '/images/courses/course-2.jpg',
            'instructor_name' => 'Yue Yu',
            'type' => 'mini',
            'is_published' => true,
            'sort_order' => 2,
            'portaly_url' => 'https://portaly.cc/yueyuknows/products/2',
            'portaly_product_id' => 'prod_002',
        ]);

        Course::create([
            'name' => '個人品牌經營完整攻略',
            'tagline' => '從零開始打造有影響力的個人品牌',
            'description' => '這是一門完整的個人品牌經營課程，涵蓋定位策略、內容創作、社群經營、變現模式等核心主題。無論你是想建立副業還是發展自媒體事業，這門課都能給你完整的框架和實戰技巧。',
            'price' => 4990,
            'thumbnail' => '/images/courses/course-3.jpg',
            'instructor_name' => 'Yue Yu',
            'type' => 'full',
            'is_published' => true,
            'sort_order' => 3,
            'portaly_url' => 'https://portaly.cc/yueyuknows/products/3',
            'portaly_product_id' => 'prod_003',
        ]);

        Course::create([
            'name' => '財務自由的第一堂課',
            'tagline' => '建立正確的金錢觀念與投資思維',
            'description' => '想要財務自由，首先要有正確的觀念。這門講座將破解常見的理財迷思，教你如何建立被動收入系統，以及經營者如何思考金錢與投資。',
            'price' => 790,
            'thumbnail' => '/images/courses/course-4.jpg',
            'instructor_name' => 'Yue Yu',
            'type' => 'lecture',
            'is_published' => true,
            'sort_order' => 4,
            'portaly_url' => 'https://portaly.cc/yueyuknows/products/4',
            'portaly_product_id' => 'prod_004',
        ]);

        Course::create([
            'name' => '高效溝通技巧',
            'tagline' => '讓你的每一次對話都能達成目標',
            'description' => '溝通是所有成功的基礎。這門課將教你如何在不同場景下有效溝通，包含向上管理、跨部門協調、客戶談判等實用技巧。每個單元都有實際演練和反饋。',
            'price' => 2490,
            'thumbnail' => '/images/courses/course-5.jpg',
            'instructor_name' => 'Yue Yu',
            'type' => 'mini',
            'is_published' => true,
            'sort_order' => 5,
            'portaly_url' => 'https://portaly.cc/yueyuknows/products/5',
            'portaly_product_id' => 'prod_005',
        ]);
    }
}
