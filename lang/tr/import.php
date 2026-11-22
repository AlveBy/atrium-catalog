<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Ürünleri içe aktar',
    'permission' => 'İçe aktarma',

    'index' => [
        'products' => 'Ürünler',
        'upload' => 'Dosya yükle',
        'hint' => 'CSV veya Excel (xlsx), en fazla :size MB. İlk satırda sütun adları bulunur. Kodlama (UTF-8 veya windows-1251) ve ayırıcı (noktalı virgül veya virgül) kendiliğinden tanınır.',
        'choose' => 'İçe aktarılacak dosya',
        'submit' => 'Yükle',
        'history' => 'Önceki içe aktarmalar',
        'empty' => 'Henüz hiçbir şey içe aktarılmadı.',
        'file' => 'Dosya',
        'status' => 'Durum',
        'result' => 'Sonuç',
        'counts' => 'oluşturulan :created, güncellenen :updated, hata :failed',
        'user' => 'Kim',
        'console' => 'konsol',
        'date' => 'Ne zaman',
    ],

    'upload' => [
        'file' => 'dosya',
    ],

    'statuses' => [
        'new' => 'Kontrol bekliyor',
        'checking' => 'Kontrol ediliyor',
        'checked' => 'Kontrol edildi',
        'importing' => 'İçe aktarılıyor',
        'done' => 'Tamamlandı',
        'failed' => 'Başarısız',
        'cancelled' => 'Durduruldu',
    ],

    'delimiters' => [
        ';' => 'noktalı virgül',
        ',' => 'virgül',
        "\t" => 'sekme',
    ],

    'show' => [
        'back' => 'Tüm içe aktarmalar',
        'file' => ':format, :encoding, ayırıcı: :delimiter, :rows satır',
        'workbook' => ':format, :rows satır',
        'checking' => 'Dosya kontrol ediliyor…',
        'importing' => 'İçe aktarılıyor…',
        'progress' => ':processed / :total satır',
        'cancel' => 'Durdur',
        'cancel_confirm' => 'Durdurulsun mu? İçe aktarılanlar kalır.',
        'report' => 'Rapor',
        'check_report' => 'Kontrol',
        'checked' => 'Kontrol bitti: henüz hiçbir şey yazılmadı. İçe aktarma şunları yapacak.',
        'done' => 'İçe aktarma bitti.',
        'cancelled' => 'İçe aktarma durduruldu. Daha önce içe aktarılanlar kaldı.',
        'counts' => [
            'created' => 'Oluşturulan',
            'updated' => 'Güncellenen',
            'skipped' => 'Atlanan',
            'failed' => 'Hata',
            'sections' => 'Oluşturulan bölümler',
        ],
        'will' => [
            'created' => 'Oluşturulacak',
            'updated' => 'Güncellenecek',
            'skipped' => 'Atlanacak',
            'failed' => 'Hata',
            'sections' => 'Oluşturulacak bölümler',
        ],
        'row' => 'Satır',
        'outcome' => 'Sonuç',
        'reason' => 'Neden',
        'skipped_row' => 'atlandı',
        'failed_row' => 'hata',
        'unlisted' => 'Ayrıca :count satır sayıldı ama listede gösterilmedi.',
        'run' => 'İçe aktar',
        'run_hint' => 'Hatalı satırlar dışarıda bırakılır; gerisi kontrolün gösterdiği gibi içe aktarılır.',
        'mapping' => 'Sütunlar',
        'mapping_hint' => 'Her sütunun hangi alanı doldurduğu, sütun adlarına bakılarak tahmin edildi. Boş hücre alanı olduğu gibi bırakır.',
        'column' => 'Sütun',
        'unnamed' => 'Sütun :number',
        'samples' => 'İlk satırlarda',
        'target' => 'Alan',
        'skip' => '— içe aktarma —',
        'options' => 'Nasıl içe aktarılacak',
        'key_hint' => 'Aynı değere sahip ürün güncellenir; olmayan ürün oluşturulur.',
        'separator_hint' => 'Bir bölümü içinde bulunduğu bölümden ayırır: “Giyim / Ceketler”.',
        'check' => 'Kontrol et',
        'check_again' => 'Yeniden kontrol et',
        'check_hint' => 'Kontrol tüm dosyayı okur ve hiçbir şey yazmaz: kaç ürünün oluşturulacağını ve güncelleneceğini, hangi satırlarda hata olduğunu göreceksiniz.',
    ],

    'settings' => [
        'mapping' => 'Sütunlar',
        'key' => 'Ürünler şuna göre bulunsun',
        'mode' => 'Ne yapılsın',
        'create_sections' => 'Olmayan bölümleri oluştur',
        'separator' => 'Bölüm yolu ayırıcısı',
    ],

    'modes' => [
        'both' => 'Yeni ürünleri oluştur, mevcut olanları güncelle',
        'create' => 'Yalnızca yeni ürünleri oluştur',
        'update' => 'Yalnızca mevcut ürünleri güncelle',
    ],

    'validation' => [
        'twice' => 'Bir alanı yalnızca tek sütun doldurur; birden fazla kez seçilen: :fields.',
        'key' => 'Ürünler “:field” alanına göre bulunuyor, ancak hiçbir sütun bu alanı doldurmuyor.',
    ],

    'failures' => [
        'empty' => 'Dosyada satır yok.',
        'format' => 'Bu ne CSV ne de Excel (xlsx). Eski bir .xls dosyası Excel ile .xlsx veya CSV olarak yeniden kaydedilebilir.',
        'unreadable' => 'Excel dosyası okunamıyor: bozuk veya parola korumalı olabilir.',
        'crashed' => 'İçe aktarma, sunucudaki bir hata yüzünden durdu. Hata günlüğe yazıldı; içe aktarılanlar kaldı.',
        'missing' => ':file dosyası yok.',
        'download' => 'Dosya :url adresinden indirilemedi.',
        'column' => '--map için böyle bir sütun yok: “:column”. Şöyle yazın: “Sütun=alan”.',
    ],

    'problems' => [
        'no_key' => '“:field” boş: ürün bulunamaz ve oluşturulamaz.',
        'exists' => '“:key” ürünü zaten var, yalnızca yeni ürünler oluşturuluyor.',
        'missing' => '“:key” ürünü yok, yalnızca mevcut ürünler güncelleniyor.',
        'database' => 'Veritabanı satırı kabul etmedi. Hata günlüğe yazıldı.',
        'not_bool' => '“:field”: “:value” ne evet ne hayır.',
        'not_option' => '“:field”: “:value” diye bir seçenek yok.',
        'ambiguous_option' => '“:field”: birden fazla seçeneğin adı “:value”.',
        'section_missing' => '“:section” adlı bölüm yok.',
        'section_ambiguous' => 'Birden fazla bölümün adı “:section”: yolunu yazın, “Üst bölüm / :section”.',
        'image_url' => 'Görsel adresi geçerli değil: :url',
        'image_path' => 'Görsel yoluna izin verilmiyor: :path',
        'image_type' => 'Bu bir görsel değil (JPEG, PNG, GIF, WebP veya SVG): :path',
        'image_missing' => 'Sitede :path görseli yok.',
        'image_host' => 'Görsel ağ içindeki bir adreste duruyor, bunlar indirilmez: :url',
        'image_download' => 'Görsel indirilemedi: :url',
        'image_large' => 'Görsel :max KB değerinden büyük: :url',
    ],

    'console' => [
        'source' => 'Dosyayı veya adresini belirtin.',
        'like' => ':id numaralı içe aktarma yok.',
        'report' => 'Satır: :processed. Oluşturulan: :created, güncellenen: :updated, atlanan: :skipped, hata: :failed, oluşturulan bölüm: :sections.',
        'row' => 'Satır :row: :messages',
    ],
];
