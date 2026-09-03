<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->string('title_az');
            $table->string('title_en')->nullable();
            $table->string('title_ru')->nullable();
            $table->longText('body_az')->nullable();
            $table->longText('body_en')->nullable();
            $table->longText('body_ru')->nullable();
            $table->timestamps();
        });

        $now = now();
        $pages = [
            [
                'slug' => 'about',
                'sort_order' => 10,
                'title_az' => 'Haqqımızda',
                'title_en' => 'About us',
                'title_ru' => 'О нас',
                'body_az' => '<p>My Sancho ailələri yerli xidmət göstərənlərlə birləşdirən marketplace-dir.</p>',
                'body_en' => '<p>My Sancho is a marketplace connecting families with local service providers.</p>',
                'body_ru' => '<p>My Sancho — маркетплейс, связывающий семьи с местными исполнителями услуг.</p>',
            ],
            [
                'slug' => 'how-it-works',
                'sort_order' => 20,
                'title_az' => 'İşləmə prinsipi',
                'title_en' => 'How it works',
                'title_ru' => 'Как это работает',
                'body_az' => '<p>Səs və ya mətnlə sorğu yazın → AI kateqoriya, məkan və vaxtı oxuyur → uyğun xidmətçilər çıxır → CONNECT ilə əlaqə.</p>',
                'body_en' => '<p>Record or type a request → AI parses category, place and time → matching providers appear → CONNECT to chat.</p>',
                'body_ru' => '<p>Запишите или напишите запрос → ИИ определяет категорию, место и время → подходящие исполнители → CONNECT для связи.</p>',
            ],
            [
                'slug' => 'terms',
                'sort_order' => 30,
                'title_az' => 'Şərtlər',
                'title_en' => 'Terms',
                'title_ru' => 'Условия',
                'body_az' => '<p>My Sancho-dan istifadə edərək platformanın istifadə şərtlərini qəbul etmiş olursunuz. Mətn admin panelindən yenilənə bilər.</p>',
                'body_en' => '<p>By using My Sancho you accept these terms of use. This text can be updated from the admin panel.</p>',
                'body_ru' => '<p>Используя My Sancho, вы принимаете условия использования. Текст можно обновить в админ-панели.</p>',
            ],
            [
                'slug' => 'rules',
                'sort_order' => 40,
                'title_az' => 'Qaydalar',
                'title_en' => 'Rules',
                'title_ru' => 'Правила',
                'body_az' => '<p>Hörmətli ünsiyyət, düzgün profil məlumatı və təhlükəsiz ödəniş qaydalarına əməl edin.</p>',
                'body_en' => '<p>Follow respectful communication, accurate profile data, and safe payment practices.</p>',
                'body_ru' => '<p>Соблюдайте уважительное общение, точные данные профиля и безопасные платежи.</p>',
            ],
            [
                'slug' => 'privacy',
                'sort_order' => 50,
                'title_az' => 'Məxfilik siyasəti',
                'title_en' => 'Privacy policy',
                'title_ru' => 'Политика конфиденциальности',
                'body_az' => '<p>Telefon nömrəsi, məkan və mesajlar xidmətin işləməsi üçün saxlanılır. Məlumat üçün admin paneli mətnini redaktə edin.</p>',
                'body_en' => '<p>Phone number, location and messages are stored to operate the service. Edit this text in the admin panel.</p>',
                'body_ru' => '<p>Номер телефона, локация и сообщения хранятся для работы сервиса. Отредактируйте текст в админ-панели.</p>',
            ],
        ];

        foreach ($pages as $page) {
            DB::table('static_pages')->insert([
                ...$page,
                'is_published' => true,
                'show_in_menu' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('static_pages');
    }
};
