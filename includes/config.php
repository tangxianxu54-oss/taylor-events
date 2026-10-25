<?php
/**
 * 全局配置：站点信息、时区、数据库路径、上传限制与图片地址
 */

date_default_timezone_set('America/Indiana/Indianapolis');

const APP_NAME = 'Taylor Events';
const CAMPUS_NAME = 'Taylor University';
const CAMPUS_TZ = 'America/Indiana/Indianapolis';
const DEMO_MODE = true;

const DB_PATH = __DIR__ . '/../data/app.sqlite';
const UPLOAD_PATH = __DIR__ . '/../uploads';
const MAX_UPLOAD_BYTES = 4 * 1024 * 1024;

const SESSION_NAME = 'taylor_events_session';

/**
 * 生成 AI 图片地址，统一走平台提供的图片接口
 */
function ai_image_url(string $prompt, string $size = 'landscape_4_3'): string
{
    return 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt='
        . rawurlencode($prompt)
        . '&image_size=' . $size;
}

/**
 * 首页大图 banner
 */
function hero_image_url(): string
{
    return ai_image_url(
        'Taylor University campus in autumn, historic brick buildings, golden hour sunlight, students walking across a tree-lined quad, wide cinematic photograph, warm tones, no text',
        'landscape_16_9'
    );
}

/**
 * 分类活动封面图，同一分类复用一张，避免每个活动都请求图片
 */
function category_cover_url(string $slug): string
{
    $prompts = [
        'sports' => 'college students playing an outdoor soccer match on a green campus field, energetic action, sunny afternoon, no text',
        'arts' => 'university art gallery opening with students viewing colorful paintings, warm ambient lighting, no text',
        'academic' => 'modern university lecture hall with students and a professor presenting, warm natural light, no text',
        'service' => 'college volunteers planting in a community garden, teamwork, sunny day, no text',
        'social' => 'college students having a picnic on a campus lawn at golden hour, string lights, relaxed, no text',
        'wellness' => 'bright university yoga studio with students on mats, large windows, calm morning light, no text',
    ];

    $prompt = $prompts[$slug] ?? 'university campus event with students, warm natural light, no text';

    return ai_image_url($prompt, 'landscape_4_3');
}
