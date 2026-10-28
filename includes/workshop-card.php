<?php
/**
 * Workshop / Seminar 卡片组件。调用前需定义 $workshop（已 decorate 的数组）。
 */
$wsLink = 'workshop.php?id=' . (int) $workshop['id'];
$wsModeIcon = match ($workshop['mode']) {
    'online' => 'video',
    'hybrid' => 'video',
    default => 'map-pin',
};
?>
<article class="event-card workshop-card" data-workshop-id="<?= (int) $workshop['id'] ?>">
    <a class="event-card__media" href="<?= e($wsLink) ?>" style="--cat-color: <?= e($workshop['topic_color']) ?>" tabindex="-1" aria-hidden="true">
        <img class="event-card__img" src="<?= e($workshop['cover_url']) ?>" alt="" loading="lazy" onerror="this.remove()">
        <span class="event-card__cat">
            <?= icon($workshop['topic_icon'], 14) ?>
            <?= e($workshop['topic_name']) ?>
        </span>
        <?php if ($workshop['is_past']): ?>
            <span class="event-card__badge">Past</span>
        <?php elseif ($workshop['is_full']): ?>
            <span class="event-card__badge event-card__badge--full">Full</span>
        <?php endif; ?>
        <span class="ws-mode-tag"><?= icon($wsModeIcon, 13) ?> <?= e($workshop['mode_label']) ?></span>
        <span class="event-card__date">
            <strong><?= e($workshop['date_number']) ?></strong>
            <small><?= e($workshop['month_label']) ?></small>
        </span>
    </a>
    <div class="event-card__body">
        <div class="event-card__kicker">
            <span><?= e($workshop['day_label']) ?></span>
            <i class="dot"></i>
            <span><?= e($workshop['time_label']) ?></span>
        </div>
        <h3 class="event-card__title">
            <a href="<?= e($wsLink) ?>"><?= e($workshop['title']) ?></a>
        </h3>
        <p class="event-card__meta">
            <?= icon('microphone', 15) ?>
            <span><?= e($workshop['speaker']) ?></span>
        </p>
        <div class="event-card__footer">
            <div class="capacity">
                <div class="capacity__text">
                    <?= icon('users', 15) ?>
                    <span><strong><?= (int) $workshop['going_count'] ?></strong> / <?= (int) $workshop['capacity'] ?> booked</span>
                </div>
                <div class="progress<?= $workshop['is_full'] ? ' progress--full' : '' ?>">
                    <span style="width: <?= (int) $workshop['fill_percent'] ?>%"></span>
                </div>
            </div>
            <span class="event-card__cta" aria-hidden="true"><?= icon('arrow-right', 17) ?></span>
        </div>
    </div>
</article>
