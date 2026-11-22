<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '商品のインポート',
    'permission' => 'インポート',

    'index' => [
        'products' => '商品',
        'upload' => 'ファイルをアップロード',
        'hint' => 'CSV または Excel (xlsx)、:size MB まで。1行目に列名を入れてください。文字コード（UTF-8 または windows-1251）と区切り文字（セミコロンまたはカンマ）は自動で判別されます。',
        'choose' => 'インポートするファイル',
        'submit' => 'アップロード',
        'history' => '過去のインポート',
        'empty' => 'まだ何もインポートされていません。',
        'file' => 'ファイル',
        'status' => '状態',
        'result' => '結果',
        'counts' => '作成 :created 件、更新 :updated 件、エラー :failed 件',
        'user' => '実行者',
        'console' => 'コンソール',
        'date' => '日時',
    ],

    'upload' => [
        'file' => 'ファイル',
    ],

    'statuses' => [
        'new' => 'チェック待ち',
        'checking' => 'チェック中',
        'checked' => 'チェック済み',
        'importing' => 'インポート中',
        'done' => '完了',
        'failed' => '失敗',
        'cancelled' => '停止',
    ],

    'delimiters' => [
        ';' => 'セミコロン',
        ',' => 'カンマ',
        "\t" => 'タブ',
    ],

    'show' => [
        'back' => 'すべてのインポート',
        'file' => ':format、:encoding、区切り文字：:delimiter、:rows 行',
        'workbook' => ':format、:rows 行',
        'checking' => 'ファイルをチェックしています…',
        'importing' => 'インポートしています…',
        'progress' => ':total 行中 :processed 行',
        'cancel' => '停止',
        'cancel_confirm' => '停止しますか？インポート済みの分はそのまま残ります。',
        'report' => 'レポート',
        'check_report' => 'チェック',
        'checked' => 'チェックが終わりました。まだ何も書き込まれていません。インポートでは次のとおりに処理されます。',
        'done' => 'インポートが完了しました。',
        'cancelled' => 'インポートを停止しました。インポート済みの分はそのまま残ります。',
        'counts' => [
            'created' => '作成',
            'updated' => '更新',
            'skipped' => 'スキップ',
            'failed' => 'エラー',
            'sections' => '作成した区分',
        ],
        'will' => [
            'created' => '作成予定',
            'updated' => '更新予定',
            'skipped' => 'スキップ予定',
            'failed' => 'エラー',
            'sections' => '作成する区分',
        ],
        'row' => '行',
        'outcome' => '処理',
        'reason' => '理由',
        'skipped_row' => 'スキップ',
        'failed_row' => 'エラー',
        'unlisted' => 'このほか :count 行が集計されていますが、一覧には表示されません。',
        'run' => 'インポート',
        'run_hint' => 'エラーのある行は除外され、残りはチェックの結果どおりにインポートされます。',
        'mapping' => '列',
        'mapping_hint' => '各列がどのフィールドに入るかを、列名から推測したものです。セルが空の場合、そのフィールドは変更されません。',
        'column' => '列',
        'unnamed' => '列 :number',
        'samples' => '先頭の行',
        'target' => 'フィールド',
        'skip' => '— インポートしない —',
        'options' => 'インポートの方法',
        'key_hint' => '同じ値の商品は更新され、存在しない商品は作成されます。',
        'separator_hint' => '区分と、その親の区分を区切ります：「衣類 / ジャケット」。',
        'check' => 'チェック',
        'check_again' => '再チェック',
        'check_hint' => 'チェックではファイル全体を読み込みますが、何も書き込みません。作成・更新される商品の数と、エラーのある行がわかります。',
    ],

    'settings' => [
        'mapping' => '列',
        'key' => '商品の照合に使う項目',
        'mode' => '処理内容',
        'create_sections' => '存在しない区分を作成する',
        'separator' => '区分パスの区切り文字',
    ],

    'modes' => [
        'both' => '新規商品を作成し、既存商品を更新する',
        'create' => '新規商品のみ作成する',
        'update' => '既存商品のみ更新する',
    ],

    'validation' => [
        'twice' => '1つのフィールドに指定できる列は1つだけですが、複数回選択されています：:fields。',
        'key' => '商品は「:field」で照合されますが、これを入れる列がありません。',
    ],

    'failures' => [
        'empty' => 'ファイルに行がありません。',
        'format' => 'CSV でも Excel (xlsx) でもありません。古い .xls ファイルは、Excel で .xlsx または CSV として保存し直せます。',
        'unreadable' => 'Excel ファイルを読み込めません。破損しているか、パスワードで保護されている可能性があります。',
        'crashed' => 'サーバーでエラーが発生し、インポートが停止しました。エラーはログに記録されました。インポート済みの分はそのまま残ります。',
        'missing' => 'ファイル :file が見つかりません。',
        'download' => ':url からファイルをダウンロードできませんでした。',
        'column' => '--map に指定した列がありません：「:column」。「列名=フィールド」の形式で書いてください。',
    ],

    'problems' => [
        'no_key' => '「:field」が空です。商品を検索することも作成することもできません。',
        'exists' => '商品「:key」はすでにあり、作成されるのは新規商品のみです。',
        'missing' => '商品「:key」がなく、更新されるのは既存商品のみです。',
        'database' => 'データベースが行を受け付けませんでした。エラーはログに記録されました。',
        'not_bool' => '「:field」：「:value」は「はい」でも「いいえ」でもありません。',
        'not_option' => '「:field」：選択肢「:value」はありません。',
        'ambiguous_option' => '「:field」：「:value」という名前の選択肢が複数あります。',
        'section_missing' => '区分「:section」がありません。',
        'section_ambiguous' => '「:section」という名前の区分が複数あります。パスで指定してください：「親区分 / :section」。',
        'image_url' => '画像のアドレスが正しくありません：:url',
        'image_path' => '使用できない画像のパスです：:path',
        'image_type' => '画像ではありません（JPEG, PNG, GIF, WebP, SVG）：:path',
        'image_missing' => 'サイトに画像 :path がありません。',
        'image_host' => '画像がネットワーク内部のアドレスにあるため取得されません：:url',
        'image_download' => '画像をダウンロードできませんでした：:url',
        'image_large' => '画像が :max KB を超えています：:url',
    ],

    'console' => [
        'source' => 'ファイルまたはその URL を指定してください。',
        'like' => 'インポート番号 :id はありません。',
        'report' => '行数：:processed。作成：:created、更新：:updated、スキップ：:skipped、エラー：:failed、作成した区分：:sections。',
        'row' => ':row 行目：:messages',
    ],
];
