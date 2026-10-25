<?php
/**
 * 活动卡片组件。调用前需定义 $event（已 decorate 的数组）。
 */
$eventLink = 'event.php?id=' . (int) $event['id'];
?>
<article class="event-card" data-event-id="<?= (int) $event['id'] ?>">
    <a class="event-card__media" href="<?= e($eventLink) ?>" style="--cat-color: <?= e($event['category_color']) ?>" tabindex="-1" aria-hidden="true">
        <img class="event-card__img" src="<?= e($event['cover_url']) ?>" alt="" loading="lazy" onerror="this.remove()">
        <span class="event-card__cat">
            <?= icon($event['category_icon'], 14) ?>
            <?= e($event['category_name']) ?>
        </span>
        <?php if ($event['is_past']): ?>
            <span class="event-card__badge">Past event</span>
        <?php elseif ($event['is_full']): ?>
            <span class="event-card__badge event-card__badge--full">Full</span>
        <?php endif; ?>
        <span class="event-card__date">
            <strong><?= e($event['date_number']) ?></strong>
            <small><?= e($event['month_label']) ?></small>
        </span>
    </a>
    <div class="event-card__body">
        <div class="event-card__kicker">
            <span><?= e($event['day_label']) ?></span>
            <i class="dot"></i>
            <span><?= e($event['time_label']) ?></span>
        </div>
        <h3 class="event-card__title">
            <a href="<?= e($eventLink) ?>"><?= e($event['title']) ?></a>
        </h3>
        <p class="event-card__meta">
            <?= icon('map-pin', 15) ?>
            <span><?= e($event['location']) ?></span>
        </p>
        <div class="event-card__footer">
            <div class="capacity">
                <div class="capacity__text">
                    <?= icon('users', 15) ?>
                    <span><strong><?= (int) $event['going_count'] ?></strong> / <?= (int) $event['capacity'] ?> going</span>
                </div>
                <div class="progress<?= $event['is_full'] ? ' progress--full' : '' ?>">
                    <span style="width: <?= (int) $event['fill_percent'] ?>%"></span>
                </div>
            </div>
            <span class="event-card__cta" aria-hidden="true"><?= icon('arrow-right', 17) ?></span>
        </div>
    </div>
</article>
