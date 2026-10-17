<?php
// Quick Add Bottom Sheet / Modal (Alpine.js driven)
?>
<div x-data="quickAddSheet()" 
     x-show="isOpen" 
     x-cloak 
     @keydown.escape.window="close()" 
     style="position: fixed; inset: 0; z-index: 999; display: flex; align-items: flex-end; justify-content: center;">

  <!-- Backdrop -->
  <div x-show="isOpen" 
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @click="close()" 
       style="position: absolute; inset: 0; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
  </div>

  <!-- Sheet Container -->
  <div x-show="isOpen"
       x-transition:enter="transition ease-out duration-250"
       x-transition:enter-start="transform translate-y-full"
       x-transition:enter-end="transform translate-y-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="transform translate-y-0"
       x-transition:leave-end="transform translate-y-full"
       style="position: relative; width: 100%; max-width: 540px; background-color: var(--bg-surface); border-radius: 24px 24px 0 0; box-shadow: var(--shadow-sheet); max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color); border-bottom: none;">

    <!-- Drag / Header Bar -->
    <div style="padding: 12px 20px 8px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color);">
      <!-- Type Switcher Tabs -->
      <div style="display: flex; background-color: var(--bg-subtle); border-radius: var(--radius-full); padding: 3px;">
        <button type="button" 
                @click="setType('expense')" 
                :class="type === 'expense' ? 'btn-danger' : 'btn-ghost'"
                style="padding: 6px 14px; font-size: 0.8125rem; border-radius: var(--radius-full); min-height: 32px;">
          Expense
        </button>
        <button type="button" 
                @click="setType('income')" 
                :class="type === 'income' ? 'btn-primary' : 'btn-ghost'"
                style="padding: 6px 14px; font-size: 0.8125rem; border-radius: var(--radius-full); min-height: 32px; background-color: type === 'income' ? var(--color-income) : 'transparent'; color: type === 'income' ? '#fff' : 'inherit';">
          Income
        </button>
        <button type="button" 
                @click="setType('transfer')" 
                :class="type === 'transfer' ? 'btn-primary' : 'btn-ghost'"
                style="padding: 6px 14px; font-size: 0.8125rem; border-radius: var(--radius-full); min-height: 32px;">
          Transfer
        </button>
      </div>

      <button type="button" class="btn btn-ghost btn-icon" @click="close()" style="width: 32px; height: 32px;">
        ✕
      </button>
    </div>

    <!-- Scrollable Body -->
    <div style="overflow-y: auto; padding: 16px 20px; flex: 1;">

      <!-- Big Numeric Amount Display -->
      <div style="text-align: center; margin-bottom: 16px; background-color: var(--bg-subtle); padding: 16px; border-radius: var(--radius-lg);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--ink-muted); margin-bottom: 2px;">
          <span x-text="type.toUpperCase()"></span> AMOUNT (BDT ৳)
        </div>
        <div style="font-size: 2.25rem; font-weight: 800; color: var(--ink-primary); line-height: 1;" class="tabular-nums">
          ৳ <span x-text="amountDisplay || '0'"></span>
        </div>
      </div>

      <!-- Category Chips Grid (Single-Tap Choice) -->
      <div x-show="type !== 'transfer'" style="margin-bottom: 16px;">
        <div style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-secondary); margin-bottom: 8px;">
          Select Category
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 140px; overflow-y: auto; padding-bottom: 4px;">
          <template x-for="cat in filteredCategories" :key="cat.id">
            <button type="button" 
                    @click="selectedCategoryId = cat.id"
                    :style="{ 
                      backgroundColor: selectedCategoryId == cat.id ? (cat.color || 'var(--color-primary)') : 'var(--bg-subtle)',
                      color: selectedCategoryId == cat.id ? '#ffffff' : 'var(--ink-primary)',
                      border: selectedCategoryId == cat.id ? '1px solid transparent' : '1px solid var(--border-color)'
                    }"
                    style="padding: 6px 12px; border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 600; cursor: pointer; transition: all 150ms ease; display: inline-flex; align-items: center; gap: 6px;">
              <span x-text="cat.name"></span>
            </button>
          </template>
        </div>
      </div>

      <!-- Account Selectors -->
      <div class="grid-2" style="margin-bottom: 16px; gap: 12px;">
        <div>
          <label style="font-size: 0.75rem; font-weight: 600; color: var(--ink-muted); display: block; margin-bottom: 4px;">
            <span x-text="type === 'transfer' ? 'From Account' : 'Account'"></span>
          </label>
          <select class="form-select" x-model="selectedAccountId" style="min-height: 38px; padding: 6px 10px; font-size: 0.875rem;">
            <?php foreach ($accounts ?? [] as $acc): ?>
              <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?> (<?= money($acc['current_balance']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div x-show="type === 'transfer'">
          <label style="font-size: 0.75rem; font-weight: 600; color: var(--ink-muted); display: block; margin-bottom: 4px;">To Account</label>
          <select class="form-select" x-model="selectedToAccountId" style="min-height: 38px; padding: 6px 10px; font-size: 0.875rem;">
            <?php foreach ($accounts ?? [] as $acc): ?>
              <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div x-show="type !== 'transfer'">
          <label style="font-size: 0.75rem; font-weight: 600; color: var(--ink-muted); display: block; margin-bottom: 4px;">Date</label>
          <input type="date" class="form-input" x-model="txDate" style="min-height: 38px; padding: 6px 10px; font-size: 0.875rem;">
        </div>
      </div>

      <!-- Optional Details Collapsible (Note & Person) -->
      <div style="margin-bottom: 16px;">
        <input type="text" class="form-input" x-model="note" placeholder="Add optional note or description..." style="min-height: 38px; font-size: 0.875rem; margin-bottom: 8px;">
      </div>

      <!-- Large Keypad for 2-Tap Entry -->
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px;">
        <template x-for="btn in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', '⌫']">
          <button type="button" 
                  @click="handleKeypad(btn)"
                  class="btn btn-secondary" 
                  style="font-size: 1.25rem; font-weight: 700; height: 50px; border-radius: var(--radius-md); padding: 0;">
            <span x-text="btn"></span>
          </button>
        </template>
      </div>

      <!-- Save & Repeat Actions -->
      <div style="display: flex; gap: 8px;">
        <button type="button" 
                class="btn btn-primary" 
                style="flex: 1; padding: 14px; font-size: 1rem; font-weight: 700;"
                :disabled="!amountDisplay || amountDisplay === '0' || isSaving"
                @click="saveTransaction()">
          <span x-text="isSaving ? 'Saving...' : 'Save Transaction'"></span>
        </button>
      </div>

    </div>

  </div>
</div>

<script>
function quickAddSheet() {
  return {
    isOpen: false,
    type: 'expense',
    amountDisplay: '',
    selectedCategoryId: null,
    selectedAccountId: '<?= $accounts[0]['id'] ?? '' ?>',
    selectedToAccountId: '<?= $accounts[1]['id'] ?? '' ?>',
    txDate: '<?= date('Y-m-d') ?>',
    note: '',
    isSaving: false,

    allCategories: <?= json_encode($categories ?? []) ?>,

    get filteredCategories() {
      return this.allCategories.filter(c => c.type === this.type);
    },

    init() {
      // Listen for quick add trigger event
      window.addEventListener('open-quick-add', (e) => {
        this.open(e.detail?.type || 'expense');
      });

      // Automatically select first category of current type
      this.syncDefaultCategory();
    },

    open(type = 'expense') {
      this.type = type;
      this.amountDisplay = '';
      this.note = '';
      this.txDate = '<?= date('Y-m-d') ?>';
      this.syncDefaultCategory();
      this.isOpen = true;
    },

    close() {
      this.isOpen = false;
    },

    setType(newType) {
      this.type = newType;
      this.syncDefaultCategory();
    },

    syncDefaultCategory() {
      const available = this.filteredCategories;
      if (available.length > 0 && !this.selectedCategoryId) {
        this.selectedCategoryId = available[0].id;
      }
    },

    handleKeypad(key) {
      if (key === '⌫') {
        this.amountDisplay = this.amountDisplay.slice(0, -1);
      } else if (key === '.') {
        if (!this.amountDisplay.includes('.')) {
          this.amountDisplay = (this.amountDisplay || '0') + '.';
        }
      } else {
        // Limit max 2 decimal places
        if (this.amountDisplay.includes('.')) {
          const parts = this.amountDisplay.split('.');
          if (parts[1].length >= 2) return;
        }
        if (this.amountDisplay === '0') {
          this.amountDisplay = key;
        } else {
          this.amountDisplay += key;
        }
      }
    },

    async saveTransaction() {
      const amountVal = parseFloat(this.amountDisplay);
      if (isNaN(amountVal) || amountVal <= 0) {
        window.PFM.toast('Please enter a valid amount', 'error');
        return;
      }

      this.isSaving = true;

      try {
        const payload = {
          type: this.type,
          amount: amountVal,
          account_id: this.selectedAccountId,
          to_account_id: this.type === 'transfer' ? this.selectedToAccountId : null,
          category_id: this.type !== 'transfer' ? this.selectedCategoryId : null,
          tx_date: this.txDate,
          note: this.note
        };

        const res = await window.PFM.fetch('/transactions', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.ok) {
          this.close();
          const createdId = data.id;

          // Show Toast with instant UNDO callback
          window.PFM.toast('Transaction recorded successfully!', 'success', {
            undoCallback: async () => {
              await window.PFM.fetch(`/transactions/${createdId}/delete`, { method: 'POST' });
              window.PFM.toast('Transaction undone.', 'info');
              setTimeout(() => window.location.reload(), 400);
            }
          });

          // Reload page to reflect new balances
          setTimeout(() => window.location.reload(), 1200);
        } else {
          window.PFM.toast(data.error || 'Failed to save transaction', 'error');
        }
      } catch (err) {
        window.PFM.toast('Network error saving transaction.', 'error');
      } finally {
        this.isSaving = false;
      }
    }
  };
}
</script>
