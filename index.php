<?php
$page_crumb = 'HOME';
require_once __DIR__ . '/includes/header.php';

$upcoming_sql = "SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered_count 
                 FROM events e ORDER BY e.event_date ASC, e.event_time ASC LIMIT 3";
$upcoming_res = $conn->query($upcoming_sql);
?>

<div class="row align-items-center py-4 gy-4">
    <div class="col-lg-7">
        <div class="eyebrow-text"><span class="live-dot"></span> The Campus Network Is On</div>
        <h1 class="hero-heading mb-3">Campus life.<br><span>In real time.</span></h1>
        <p class="text-muted fs-6 mb-4" style="max-width: 520px;">
            From the events you can't miss to the updates you need. Everything happening on campus, all in one place.
        </p>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="events.php" class="btn btn-cg-primary px-4 py-2">Explore events &#8599;</a>
            <a href="announcements.php" class="text-decoration-none fw-semibold text-dark small">Latest announcements &rarr;</a>
        </div>
        <div class="mt-4 pt-2 text-muted" style="font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase;">
            <span class="text-primary">&bull;</span> Discover &nbsp;&nbsp;
            <span class="text-primary">&bull;</span> Connect &nbsp;&nbsp;
            <span class="text-primary">&bull;</span> Show Up
        </div>
    </div>

    <div class="col-lg-5 text-center">
        <div class="orbit-stage">
            <div class="orbit-glow"></div>
            <div class="orbit-circle-outer"></div>

            <!-- Tilted Orbital Plane 1 -->
            <div class="orbit-plane plane-1">
                <div class="orbit-ellipse">
                    <span class="orbit-dot"></span>
                </div>
            </div>

            <!-- Tilted Orbital Plane 2 -->
            <div class="orbit-plane plane-2">
                <div class="orbit-ellipse ellipse-alt">
                    <span class="orbit-dot dot-alt"></span>
                </div>
            </div>

            <!-- Floating Feature Cards -->
            <div class="orbit-card card-float-1" style="top: 18px; left: 8px;"><span class="card-dot"></span> EVENTS</div>
            <div class="orbit-card card-float-2" style="top: 44%; right: -8px;"><span class="card-dot"></span> LIVE UPDATES</div>
            <div class="orbit-card card-float-3" style="bottom: 24px; left: 14px;"><span class="card-dot"></span> YOUR PEOPLE</div>

            <!-- Center Sphere + Blue Halo -->
            <div class="orbit-halo"></div>
            <div class="orbit-sphere">
                <span class="text-primary" style="font-weight: 800; font-size: 1.7rem; line-height: 1; letter-spacing: -0.02em;">CG</span>
                <span style="font-size: 0.54rem; font-weight: 600; letter-spacing: 0.12em; color: #64748b; margin-top: 4px; line-height: 1.25;">CAMPUS<br>CONNECTED</span>
            </div>
            <span class="orbit-index">01 / 04</span>
        </div>
    </div>
</div>

<!-- Coming Up Section -->
<div class="mt-4 pt-3">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <div class="eyebrow-text mb-0">Don't Miss The Moment</div>
            <h2 class="fw-bold fs-3 mb-0">Coming <span class="text-primary">up.</span></h2>
        </div>
        <a href="events.php" class="text-decoration-none small fw-semibold">View all events &#8599;</a>
    </div>

    <?php if ($upcoming_res && $upcoming_res->num_rows > 0): ?>
        <div class="row g-3">
            <?php while ($ev = $upcoming_res->fetch_assoc()): 
                $spots_left = max(0, (int)$ev['capacity'] - (int)$ev['registered_count']);
            ?>
                <div class="col-md-4">
                    <div class="cg-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary-subtle text-primary"><?= e($ev['category']) ?></span>
                                <span class="small text-muted"><?= date('M d, Y', strtotime($ev['event_date'])) ?></span>
                            </div>
                            <h5 class="fw-bold mb-2"><?= e($ev['title']) ?></h5>
                            <p class="small text-muted mb-3"><?= e($ev['description']) ?></p>
                        </div>
                        <div class="border-top pt-2 d-flex justify-content-between align-items-center small">
                            <span class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($ev['venue']) ?></span>
                            <?php if ($spots_left <= 0): ?>
                                <span class="badge bg-danger-subtle text-danger">Event Full</span>
                            <?php else: ?>
                                <a href="events.php" class="fw-semibold text-decoration-none"><?= $spots_left ?> spots left &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state-box">
            The next big campus moment starts here. Events will appear as organizers publish them.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>