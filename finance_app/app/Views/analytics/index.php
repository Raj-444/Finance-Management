<script src="/assets/js/chart.min.js"></script>

<div class="page-container" x-data="analyticsApp()" x-init="init()">
  <!-- Header & Filters -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 class="page-title" style="margin: 0 0 4px 0;">
        Analytics & Trends
      </h1>
      <p class="page-subtitle" style="margin: 0;">
        Track cashflow dynamics, category distributions, and multi-month trajectories.
      </p>
    </div>

    <!-- Range Pill Selector -->
    <div style="display: flex; align-items: center; gap: 6px; background: var(--surface-card); border: 1px solid var(--border-color); padding: 4px; border-radius: var(--radius-md);">
      <button type="button" @click="setRange('7d')" :class="range === '7d' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 6px 12px; font-size: 0.8125rem;">7 Days</button>
      <button type="button" @click="setRange('30d')" :class="range === '30d' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 6px 12px; font-size: 0.8125rem;">30 Days</button>
      <button type="button" @click="setRange('month')" :class="range === 'month' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 6px 12px; font-size: 0.8125rem;">This Month</button>
    </div>
  </div>

  <!-- Loading Indicator -->
  <div x-show="loading" style="text-align: center; padding: 40px; color: var(--ink-muted);">
    <div class="spinner" style="margin: 0 auto 12px;"></div>
    <span>Loading financial analytics...</span>
  </div>

  <div x-show="!loading" style="display: flex; flex-direction: column; gap: 24px;">
    <!-- KPI Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
      <div class="card" style="padding: 16px 20px;">
        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Total Income</span>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-success); margin-top: 6px;" x-text="formatCurrency(totalIncome)"></div>
      </div>

      <div class="card" style="padding: 16px 20px;">
        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Total Expense</span>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-danger); margin-top: 6px;" x-text="formatCurrency(totalExpense)"></div>
      </div>

      <div class="card" style="padding: 16px 20px;">
        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Net Savings</span>
        <div style="font-size: 1.5rem; font-weight: 800; margin-top: 6px;" :style="{ color: netSavings >= 0 ? 'var(--color-success)' : 'var(--color-danger)' }" x-text="formatCurrency(netSavings)"></div>
      </div>

      <div class="card" style="padding: 16px 20px;">
        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Savings Rate</span>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-primary); margin-top: 6px;" x-text="savingsRate + '%'"></div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;" class="analytics-charts-grid">
      <!-- Daily Trend Chart -->
      <div class="card">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">
          Daily Cashflow Trend
        </h3>
        <div style="position: relative; height: 280px;">
          <canvas id="dailyTrendChart"></canvas>
        </div>
      </div>

      <!-- Expense Categories Doughnut -->
      <div class="card">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">
          Spending by Category
        </h3>
        <div style="position: relative; height: 280px; display: flex; align-items: center; justify-content: center;">
          <canvas id="categoryChart"></canvas>
        </div>
      </div>
    </div>

    <!-- 6-Month Comparison Chart -->
    <div class="card">
      <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">
        6-Month Income vs Expense Trend
      </h3>
      <div style="position: relative; height: 260px;">
        <canvas id="monthlyChart"></canvas>
      </div>
    </div>

    <!-- Category Breakdown Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color);">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0;">
          Expense Breakdown by Category
        </h3>
      </div>
      <div class="table-responsive">
        <table class="table" style="margin: 0;">
          <thead>
            <tr>
              <th>Category</th>
              <th style="text-align: right;">Transactions</th>
              <th style="text-align: right;">Total Amount</th>
              <th style="text-align: right;">Share</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="c in categories" :key="c.category_name">
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <div :style="{ width: '12px', height: '12px', borderRadius: '50%', backgroundColor: c.category_color || '#5B5BD6' }"></div>
                    <span style="font-weight: 600; color: var(--ink-primary);" x-text="c.category_name"></span>
                  </div>
                </td>
                <td style="text-align: right; color: var(--ink-muted);" x-text="c.tx_count"></td>
                <td style="text-align: right; font-weight: 700; color: var(--ink-primary);" x-text="formatCurrency(c.total_major)"></td>
                <td style="text-align: right; color: var(--ink-muted);" x-text="(totalExpense > 0 ? Math.round((c.total_major / totalExpense) * 100) : 0) + '%'"></td>
              </tr>
            </template>
            <tr x-show="categories.length === 0">
              <td colspan="4" style="text-align: center; color: var(--ink-muted); padding: 24px;">No expense records in this range.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
@media (max-width: 900px) {
  .analytics-charts-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>

<script>
function analyticsApp() {
  return {
    range: '<?= e($range) ?>',
    startDate: '<?= e($startDate) ?>',
    endDate: '<?= e($endDate) ?>',
    loading: true,
    totalIncome: 0,
    totalExpense: 0,
    netSavings: 0,
    savingsRate: 0,
    categories: [],
    dailyTrendChart: null,
    categoryChart: null,
    monthlyChart: null,

    init() {
      this.fetchData();
    },

    setRange(newRange) {
      this.range = newRange;
      const today = new Date();
      if (newRange === '7d') {
        const d = new Date();
        d.setDate(d.getDate() - 7);
        this.startDate = d.toISOString().split('T')[0];
        this.endDate = today.toISOString().split('T')[0];
      } else if (newRange === '30d') {
        const d = new Date();
        d.setDate(d.getDate() - 30);
        this.startDate = d.toISOString().split('T')[0];
        this.endDate = today.toISOString().split('T')[0];
      } else if (newRange === 'month') {
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        this.startDate = `${y}-${m}-01`;
        this.endDate = today.toISOString().split('T')[0];
      }
      this.fetchData();
    },

    formatCurrency(val) {
      return '৳ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    fetchData() {
      this.loading = true;
      fetch(`/analytics/data?start_date=${this.startDate}&end_date=${this.endDate}`)
        .then(res => res.json())
        .then(res => {
          this.loading = false;
          if (res.ok) {
            this.renderData(res.data);
          }
        })
        .catch(err => {
          this.loading = false;
          console.error(err);
        });
    },

    renderData(data) {
      this.categories = data.categories || [];
      
      // Calculate totals
      this.totalExpense = (data.categories || []).reduce((acc, cur) => acc + cur.total_major, 0);
      this.totalIncome = (data.incomes || []).reduce((acc, cur) => acc + cur.total_major, 0);
      this.netSavings = this.totalIncome - this.totalExpense;
      this.savingsRate = this.totalIncome > 0 ? Math.max(0, Math.round((this.netSavings / this.totalIncome) * 100)) : 0;

      this.$nextTick(() => {
        this.buildDailyTrendChart(data.daily_trend || []);
        this.buildCategoryChart(data.categories || []);
        this.buildMonthlyChart(data.monthly_comparison || []);
      });
    },

    buildDailyTrendChart(trend) {
      const ctx = document.getElementById('dailyTrendChart');
      if (!ctx) return;
      if (this.dailyTrendChart) this.dailyTrendChart.destroy();

      const labels = trend.map(t => t.day.slice(5));
      const expenses = trend.map(t => t.expense_major);
      const incomes = trend.map(t => t.income_major);

      this.dailyTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Expense',
              data: expenses,
              borderColor: '#F04438',
              backgroundColor: 'rgba(240, 68, 56, 0.08)',
              borderWidth: 2,
              tension: 0.3,
              fill: true,
              pointRadius: 2,
            },
            {
              label: 'Income',
              data: incomes,
              borderColor: '#12B76A',
              backgroundColor: 'rgba(18, 183, 106, 0.08)',
              borderWidth: 2,
              tension: 0.3,
              fill: true,
              pointRadius: 2,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top', align: 'end' }
          },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
          }
        }
      });
    },

    buildCategoryChart(categories) {
      const ctx = document.getElementById('categoryChart');
      if (!ctx) return;
      if (this.categoryChart) this.categoryChart.destroy();

      if (categories.length === 0) {
        return;
      }

      const labels = categories.map(c => c.category_name);
      const data = categories.map(c => c.total_major);
      const colors = categories.map(c => c.category_color || '#5B5BD6');

      this.categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#FFFFFF',
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
          },
          cutout: '68%'
        }
      });
    },

    buildMonthlyChart(monthly) {
      const ctx = document.getElementById('monthlyChart');
      if (!ctx) return;
      if (this.monthlyChart) this.monthlyChart.destroy();

      const labels = monthly.map(m => m.month);
      const incomes = monthly.map(m => m.income_major);
      const expenses = monthly.map(m => m.expense_major);

      this.monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Income',
              data: incomes,
              backgroundColor: '#12B76A',
              borderRadius: 6,
            },
            {
              label: 'Expense',
              data: expenses,
              backgroundColor: '#F04438',
              borderRadius: 6,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top', align: 'end' }
          },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
          }
        }
      });
    }
  };
}
</script>
