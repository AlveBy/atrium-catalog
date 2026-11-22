<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Importação de produtos',
    'permission' => 'Importação',

    'index' => [
        'products' => 'Produtos',
        'upload' => 'Enviar um arquivo',
        'hint' => 'CSV ou Excel (xlsx), até :size MB. A primeira linha contém os nomes das colunas. A codificação (UTF-8 ou windows-1251) e o separador (ponto e vírgula ou vírgula) são reconhecidos automaticamente.',
        'choose' => 'Arquivo para importar',
        'submit' => 'Enviar',
        'history' => 'Importações anteriores',
        'empty' => 'Nada foi importado ainda.',
        'file' => 'Arquivo',
        'status' => 'Status',
        'result' => 'Resultado',
        'counts' => ':created criados, :updated atualizados, :failed erros',
        'user' => 'Quem',
        'console' => 'console',
        'date' => 'Quando',
    ],

    'upload' => [
        'file' => 'arquivo',
    ],

    'statuses' => [
        'new' => 'Aguardando verificação',
        'checking' => 'Verificando',
        'checked' => 'Verificado',
        'importing' => 'Importando',
        'done' => 'Concluído',
        'failed' => 'Falhou',
        'cancelled' => 'Interrompido',
    ],

    'delimiters' => [
        ';' => 'ponto e vírgula',
        ',' => 'vírgula',
        "\t" => 'tabulação',
    ],

    'show' => [
        'back' => 'Todas as importações',
        'file' => ':format, :encoding, separador: :delimiter, :rows linhas',
        'workbook' => ':format, :rows linhas',
        'checking' => 'Verificando o arquivo…',
        'importing' => 'Importando…',
        'progress' => ':processed de :total linhas',
        'cancel' => 'Interromper',
        'cancel_confirm' => 'Interromper? O que já foi importado permanece.',
        'report' => 'Relatório',
        'check_report' => 'Verificação',
        'checked' => 'A verificação terminou: nada foi gravado ainda. Veja o que a importação vai fazer.',
        'done' => 'A importação terminou.',
        'cancelled' => 'A importação foi interrompida. O que já havia sido importado permanece.',
        'counts' => [
            'created' => 'Criados',
            'updated' => 'Atualizados',
            'skipped' => 'Ignorados',
            'failed' => 'Erros',
            'sections' => 'Seções criadas',
        ],
        'will' => [
            'created' => 'Serão criados',
            'updated' => 'Serão atualizados',
            'skipped' => 'Serão ignorados',
            'failed' => 'Erros',
            'sections' => 'Seções a criar',
        ],
        'row' => 'Linha',
        'outcome' => 'Resultado',
        'reason' => 'Motivo',
        'skipped_row' => 'ignorada',
        'failed_row' => 'erro',
        'unlisted' => 'Mais :count linhas foram contadas, mas não aparecem na lista.',
        'run' => 'Importar',
        'run_hint' => 'As linhas com erros ficam de fora; o restante é importado como a verificação mostrou.',
        'mapping' => 'Colunas',
        'mapping_hint' => 'Qual campo cada coluna preenche, deduzido pelos nomes das colunas. Uma célula vazia mantém o campo como está.',
        'column' => 'Coluna',
        'unnamed' => 'Coluna :number',
        'samples' => 'Nas primeiras linhas',
        'target' => 'Campo',
        'skip' => '— não importar —',
        'options' => 'Como importar',
        'key_hint' => 'Um produto com o mesmo valor é atualizado; se não existir, é criado.',
        'separator_hint' => 'Separa uma seção daquela em que ela está: “Roupas / Jaquetas”.',
        'check' => 'Verificar',
        'check_again' => 'Verificar de novo',
        'check_hint' => 'A verificação lê o arquivo inteiro e não grava nada: você verá quantos produtos seriam criados e atualizados e em quais linhas há erros.',
    ],

    'settings' => [
        'mapping' => 'Colunas',
        'key' => 'Encontrar produtos por',
        'mode' => 'O que fazer',
        'create_sections' => 'Criar as seções que não existem',
        'separator' => 'Separador do caminho da seção',
    ],

    'modes' => [
        'both' => 'Criar produtos novos e atualizar os existentes',
        'create' => 'Apenas criar produtos novos',
        'update' => 'Apenas atualizar produtos existentes',
    ],

    'validation' => [
        'twice' => 'Um campo aceita apenas uma coluna; escolhido mais de uma vez: :fields.',
        'key' => 'Os produtos são encontrados por “:field”, mas nenhuma coluna o preenche.',
    ],

    'failures' => [
        'empty' => 'O arquivo não tem linhas.',
        'format' => 'Isto não é CSV nem Excel (xlsx). Um arquivo .xls antigo pode ser salvo no Excel como .xlsx ou CSV.',
        'unreadable' => 'Não foi possível ler o arquivo do Excel: ele pode estar danificado ou protegido por senha.',
        'crashed' => 'A importação parou por causa de um erro no servidor. Ele foi registrado; o que já havia sido importado permanece.',
        'missing' => 'O arquivo :file não existe.',
        'download' => 'Não foi possível baixar o arquivo de :url.',
        'column' => 'Não há essa coluna para --map: “:column”. Escreva assim: “Coluna=campo”.',
    ],

    'problems' => [
        'no_key' => '“:field” está vazio: o produto não pode ser encontrado nem criado.',
        'exists' => 'O produto “:key” já existe, e só produtos novos são criados.',
        'missing' => 'O produto “:key” não existe, e só produtos existentes são atualizados.',
        'database' => 'O banco de dados recusou a linha. O erro foi registrado.',
        'not_bool' => '“:field”: “:value” não é sim nem não.',
        'not_option' => '“:field”: não existe a opção “:value”.',
        'ambiguous_option' => '“:field”: mais de uma opção se chama “:value”.',
        'section_missing' => 'Não existe a seção “:section”.',
        'section_ambiguous' => 'Mais de uma seção se chama “:section”: informe o caminho, “Superior / :section”.',
        'image_url' => 'O endereço da imagem não é válido: :url',
        'image_path' => 'O caminho da imagem não é permitido: :path',
        'image_type' => 'Não é uma imagem (JPEG, PNG, GIF, WebP ou SVG): :path',
        'image_missing' => 'A imagem :path não existe no site.',
        'image_host' => 'A imagem está em um endereço dentro da rede interna, que não é baixado: :url',
        'image_download' => 'Não foi possível baixar a imagem: :url',
        'image_large' => 'A imagem é maior que :max KB: :url',
    ],

    'console' => [
        'source' => 'Informe o arquivo ou o endereço dele.',
        'like' => 'Não existe a importação de número :id.',
        'report' => 'Linhas: :processed. Criados: :created, atualizados: :updated, ignorados: :skipped, erros: :failed, seções criadas: :sections.',
        'row' => 'Linha :row: :messages',
    ],
];
