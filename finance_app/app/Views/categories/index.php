<div style="display: flex; flex-direction: column; gap: 24px;">

  <!-- Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 2px;">Categories</h2>
      <p style="font-size: 0.875rem; color: var(--ink-muted);">Organize your spending and earnings into clear categories.</p>
    </div>
  </div>

  <div class="grid-2" style="align-items: flex-start;">

    <!-- Categories List -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
      
      <!-- Expense Categories -->
      <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
          <h3 class="card-title">Expense Categories</h3>
          <span class="badge badge-expense"><?= count($expenseCategories) ?> Categories</span>
        </div>
        <div class="card-body" style="padding: 16px;">
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($expenseCategories as $cat): ?>
              <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; background-color: var(--bg-subtle); border-radius: var(--radius-full); border: 1px solid var(--border-color); font-size: 0.875rem;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= e($cat['color']) ?>;"></span>
                <span style="font-weight: 600; color: var(--ink-primary);"><?= e($cat['name']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Income Categories -->
      <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
          <h3 class="card-title">Income Categories</h3>
          <span class="badge badge-income"><?= count($incomeCategories) ?> Categories</span>
        </div>
        <div class="card-body" style="padding: 16px;">
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($incomeCategories as $cat): ?>
              <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; background-color: var(--bg-subtle); border-radius: var(--radius-full); border: 1px solid var(--border-color); font-size: 0.875rem;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= e($cat['color']) ?>;"></span>
                <span style="font-weight: 600; color: var(--ink-primary);"><?= e($cat['name']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

    <!-- Create Category Form Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">+ Add New Category</h3>
      </div>
      <div class="card-body">
        <form method="POST" action="/categories">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label">Category Name</label>
            <input type="text" name="name" class="form-input" placeholder="e.g. Subscriptions, Travel" required>
          </div>

          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="expense">Expense</option>
              <option value="income">Income</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Color Code</label>
            <input type="color" name="color" class="form-input" value="#F04438" style="height: 44px; padding: 4px; cursor: pointer;">
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-weight: 700;">
            Add Category
          </button>
        </form>
      </div>
    </div>

  </div>

</div>
