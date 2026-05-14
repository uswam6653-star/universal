zzll;

if ($eid) {
$pdo->prepare("UPDATE exercises SET name=?, category=?, type=?, difficulty=?, duration=?, thumbnail=?, media_url=?,
description=? WHERE id=?")
->execute([$name, $cat, $type, $diff, $dur, $thumb, $murl, $desc, $eid]);
} else {
$pdo->prepare("INSERT INTO exercises (name, category, type, difficulty, duration, thumbnail, media_url, description)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
->execute([$name, $cat, $type, $diff, $dur, $thumb, $murl, $desc]);
}
header("Location: training_hub.php?msg=Exercise saved successfully");
exit();
}
}

if (isset($_GET['delete_id'])) {
$pdo->prepare("DELETE FROM exercises WHERE id = ?")->execute([$_GET['delete_id']]);
header("Location: training_hub.php?msg=Exercise removed");
exit();
}

// 2. Fetch Data (Initial load)
$stmt = $pdo->query("SELECT * FROM exercises ORDER BY id DESC");
$exercises = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="fw-bold text-dark mb-1">Exercise Library 🏋️</h1>
            <p class="text-muted mb-0">Explore and manage professional workout tutorials for your clients.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <?php if (in_array($urole, ['trainer', 'hod', 'admin'])): ?>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#exerciseModal">
                    <i class="bi bi-plus-lg me-2"></i> Add Tutorial
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-dark rounded-pill px-4 btn-sm cat-filter active"
                    onclick="filterCat('All', this)">All</button>
                <?php $categories = ['Chest', 'Legs', 'Back', 'Abs', 'Arms', 'Full Body'];
                foreach ($categories as $c): ?>
                    <button class="btn btn-outline-secondary rounded-pill px-4 btn-sm cat-filter"
                        onclick="filterCat('<?= $c ?>', this)"><?= $c ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i
                        class="bi bi-search text-muted"></i></span>
                <input type="text" id="libSearch" class="form-control border-start-0 rounded-end-pill px-3"
                    placeholder="Quick search exercises..." onkeyup="filterLib()">
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="row g-4" id="exerciseContainer">
                <?php foreach ($exercises as $e): ?>
                    <div class="col-md-6 col-xl-4 lib-item" data-category="<?= $e['category'] ?>"
                        data-name="<?= strtolower($e['name']) ?>">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 transition-all hover-lift">
                            <div class="position-relative">
                                <div class="thumb-overlay" onclick='viewResource(<?= json_encode($e) ?>)'>
                                    <img src="<?= htmlspecialchars($e['thumbnail']) ?>" class="card-img-top"
                                        style="height: 180px; object-fit: cover;" alt="<?= $e['name'] ?>">
                                    <div class="play-btn-wrapper">
                                        <i
                                            class="bi <?= ($e['type'] == 'Video') ? 'bi-play-circle-fill' : 'bi-eye-fill' ?> display-4 text-white"></i>
                                    </div>
                                </div>
                                <span
                                    class="position-absolute top-0 end-0 m-3 badge rounded-pill bg-dark bg-opacity-75"><?= $e['type'] ?></span>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-primary fw-bold x-small text-uppercase"><?= $e['category'] ?></span>
                                    <span class="badge bg-light text-dark x-small"><?= $e['difficulty'] ?></span>
                                </div>
                                <h6 class="fw-bold mb-2 text-truncate"><?= htmlspecialchars($e['name']) ?></h6>
                                <p class="text-muted x-small mb-3 line-clamp-2"><?= htmlspecialchars($e['description']) ?>
                                </p>

                                <div class="d-flex gap-1">
                                    <button class="btn btn-primary btn-sm rounded-pill flex-grow-1"
                                        onclick='viewResource(<?= json_encode($e) ?>)'>View Guide</button>
                                    <?php if (in_array($urole, ['trainer', 'hod', 'admin'])): ?>
                                        <button class="btn btn-outline-success btn-sm rounded-circle"
                                            onclick='editExercise(<?= json_encode($e) ?>)'><i class="bi bi-pencil"></i></button>
                                        <a href="?delete_id=<?= $e['id'] ?>"
                                            class="btn btn-outline-danger btn-sm rounded-circle"
                                            onclick="return confirm('Delete this resource?')"><i class="bi bi-trash"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h6 class="fw-bold mb-3">Diet Tips</h6>
                <div class="mb-3 p-2 bg-info bg-opacity-10 rounded-3 border-start border-3 border-info">
                    <p class="x-small mb-1 fw-bold text-info">Bulking</p>
                    <p class="x-small text-muted mb-0">Eat at a 500 kcal surplus. 1.5g protein/lb.</p>
                </div>
                <div class="p-2 bg-warning bg-opacity-10 rounded-3 border-start border-3 border-warning">
                    <p class="x-small mb-1 fw-bold text-warning">Cutting</p>
                    <p class="x-small text-muted mb-0">Eat at a 300 kcal deficit. Increase fiber.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-dark text-white">
                <h6 class="fw-bold mb-3 text-warning">Coach's Vault</h6>
                <ul class="list-unstyled mb-0 x-small opacity-75">
                    <li class="mb-2"><i class="bi bi-star-fill text-warning me-2"></i> Always spot new lifters.</li>
                    <li class="mb-2"><i class="bi bi-star-fill text-warning me-2"></i> Record sets for form review.</li>
                    <li><i class="bi bi-star-fill text-warning me-2"></i> Track RPE for each major set.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Exercise Modal -->
<div class="modal fade" id="exerciseModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-dark text-white border-0 p-4">
                <h5 class="fw-bold mb-0" id="modalTitle">New Resource</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="exercise_id" id="edit_id">
                <div class="mb-3">
                    <label class="small fw-bold">Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Category</label>
                        <select name="category" id="edit_cat" class="form-select">
                            <option>Chest</option>
                            <option>Legs</option>
                            <option>Back</option>
                            <option>Abs</option>
                            <option>Arms</option>
                            <option>Full Body</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Type</label>
                        <select name="type" id="edit_type" class="form-select">
                            <option>Video</option>
                            <option>Image</option>
                            <option>Article</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Difficulty & Duration</label>
                    <div class="input-group">
                        <select name="difficulty" id="edit_diff" class="form-select">
                            <option>Beginner</option>
                            <option>Intermediate</option>
                            <option>Advanced</option>
                        </select>
                        <input type="text" name="duration" id="edit_dur" class="form-control"
                            placeholder="5:30 / 2 Min Read">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Thumbnail URL</label>
                    <input type="text" name="thumbnail" id="edit_thumb" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Video/Resource URL (YouTube Link)</label>
                    <input type="text" name="media_url" id="edit_murl" class="form-control"
                        placeholder="https://youtube.com/embed/...">
                </div>
                <div>
                    <label class="small fw-bold">Instructions</label>
                    <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="save_exercise" class="btn btn-dark rounded-pill w-100 fw-bold">Save
                    Tutorial</button>
            </div>
        </form>
    </div>
</div>

<!-- View Resource Modal -->
<div class="modal fade" id="resourceViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow overflow-hidden">
            <div class="modal-header bg-white border-0 px-4 pt-4 pb-0">
                <h5 class="fw-bold mb-0" id="resTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="mediaContainer" class="ratio ratio-16x9 mb-3 d-none">
                    <iframe src=""
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen class="rounded-3 border-0"></iframe>
                </div>
                <video id="videoPlayer" class="w-100 rounded-3 mb-3 d-none" controls></video>
                <div id="imageContainer" class="mb-3 d-none">
                    <img src="" class="img-fluid rounded-3 w-100 shadow-sm">
                </div>
                <p id="resDesc" class="text-muted small"></p>
                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                    <div class="d-flex gap-2">
                        <span id="resCat" class="badge bg-primary rounded-pill"></span>
                        <span id="resDiff" class="badge bg-light text-dark rounded-pill"></span>
                        <span id="resDur" class="badge bg-light text-dark rounded-pill"></span>
                    </div>
                    <a href="" id="ytLink" target="_blank" class="btn btn-danger btn-sm rounded-pill px-3 d-none">
                        <i class="bi bi-youtube me-1"></i> Open in YouTube
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCat = 'All';

    function filterCat(cat, btn) {
        currentCat = cat;
        document.querySelectorAll('.cat-filter').forEach(b => b.classList.replace('btn-dark', 'btn-outline-secondary'));
        btn.classList.replace('btn-outline-secondary', 'btn-dark');
        filterLib();
    }

    function filterLib() {
        let q = document.getElementById('libSearch').value.toLowerCase();
        document.querySelectorAll('.lib-item').forEach(item => {
            let name = item.getAttribute('data-name');
            let cat = item.getAttribute('data-category');
            let matchesSearch = name.includes(q);
            let matchesCat = (currentCat === 'All' || cat === currentCat);
            item.style.display = (matchesSearch && matchesCat) ? 'block' : 'none';
        });
    }

    function viewResource(e) {
        document.getElementById('resTitle').innerText = e.name;
        document.getElementById('resDesc').innerText = e.description;
        document.getElementById('resCat').innerText = e.category;
        document.getElementById('resDiff').innerText = e.difficulty;
        document.getElementById('resDur').innerText = e.duration;

        const mediaCont = document.getElementById('mediaContainer');
        const imgCont = document.getElementById('imageContainer');
        const ytLink = document.getElementById('ytLink');

        mediaCont.classList.add('d-none');
        imgCont.classList.add('d-none');
        ytLink.classList.add('d-none');

        if (e.type === 'Video' && e.media_url) {
            mediaCont.classList.add('d-none');
            imgCont.classList.add('d-none');
            ytLink.classList.add('d-none');
            const videoPlayer = document.getElementById('videoPlayer');
            videoPlayer.classList.add('d-none');
            videoPlayer.pause();

            if (e.media_url.endsWith('.mp4') || e.media_url.includes('cdn.')) {
                videoPlayer.classList.remove('d-none');
                videoPlayer.src = e.media_url;
                videoPlayer.play();
            } else if (e.media_url.includes('vimeo.com')) {
                mediaCont.classList.remove('d-none');
                ytLink.classList.remove('d-none');
                let vimId = e.media_url.split('vimeo.com/')[1].split('?')[0];
                mediaCont.querySelector('iframe').src = `https://player.vimeo.com/video/${vimId}?autoplay=1`;
                ytLink.innerHTML = '<i class="bi bi-vimeo me-1"></i> Open in Vimeo';
                ytLink.href = e.media_url;
            } else {
                mediaCont.classList.remove('d-none');
                ytLink.classList.remove('d-none');
                let vidId = '';
                if (e.media_url.includes('v=')) vidId = e.media_url.split('v=')[1].split('&')[0];
                else if (e.media_url.includes('youtu.be/')) vidId = e.media_url.split('youtu.be/')[1].split('?')[0];
                else if (e.media_url.includes('embed/')) vidId = e.media_url.split('embed/')[1].split('?')[0];

                if (vidId) {
                    mediaCont.querySelector('iframe').src = `https://www.youtube.com/embed/${vidId}?rel=0&autoplay=1`;
                    ytLink.href = `https://www.youtube.com/watch?v=${vidId}`;
                }
                ytLink.innerHTML = '<i class="bi bi-youtube me-1"></i> Open in YouTube';
            }
        } else {
            imgCont.classList.remove('d-none');
            imgCont.querySelector('img').src = e.thumbnail;
        }

        new bootstrap.Modal(document.getElementById('resourceViewModal')).show();
    }

    function editExercise(e) {
        document.getElementById('modalTitle').innerText = 'Update Resource';
        document.getElementById('edit_id').value = e.id;
        document.getElementById('edit_name').value = e.name;
        document.getElementById('edit_cat').value = e.category;
        document.getElementById('edit_type').value = e.type;
        document.getElementById('edit_diff').value = e.difficulty;
        document.getElementById('edit_dur').value = e.duration;
        document.getElementById('edit_thumb').value = e.thumbnail;
        document.getElementById('edit_murl').value = e.media_url;
        document.getElementById('edit_desc').value = e.description;
        new bootstrap.Modal(document.getElementById('exerciseModal')).show();
    }

    // Clear iframe src on close to stop video
    document.getElementById('resourceViewModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('mediaContainer').querySelector('iframe').src = "";
    });
</script>

<style>
    .x-small {
        font-size: 0.75rem;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
        transition: all 0.3s;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .1) !important;
    }

    .thumb-overlay {
        cursor: pointer;
        position: relative;
    }

    .play-btn-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.2);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .thumb-overlay:hover .play-btn-wrapper {
        opacity: 1;
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>