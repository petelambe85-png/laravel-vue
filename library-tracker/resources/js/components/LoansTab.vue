<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loans = ref([])
const loading = ref(false)
const error = ref(null)


const isDialogOpen = ref(false)
const selectedLoan = ref(null)
const additionalDays = ref(7) 
const options = [1, 3, 7, 14]

async function loadLoans() {
  loading.value = true
  error.value = null
  try {
    const { data } = await axios.get('/api/v1/loans')
    loans.value = data
  } catch (e) {
    error.value = 'Failed to load loans.'
  } finally {
    loading.value = false
  }
}

function openExtendDialog(loan) {
  selectedLoan.value = loan
  additionalDays.value = 7
  isDialogOpen.value = true
}

function closeDialog() {
  isDialogOpen.value = false
  selectedLoan.value = null
}

async function submitExtend() {
  if (!selectedLoan.value) return

  try {
    const { data } = await axios.put(
      `/api/v1/loans/extend/${selectedLoan.value.id}`,
      { additional_days: additionalDays.value }
    )

    const index = loans.value.findIndex(l => l.id === data.id)
    if (index !== -1) {
      loans.value[index] = data
    }

    closeDialog()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to extend loan.')
  }
}

function formatDueDisplay(loan) {
  if (!loan.due_at) return '—'

  const due = new Date(loan.due_at)
  const now = new Date()

  if (due < now) {
    return 'overdue'
  }

  const diffMs = due.getTime() - now.getTime()
  const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24))

  return `due in ${diffDays} day${diffDays === 1 ? '' : 's'}`
}

function isExtendDisabled(loan) {
  return loan.returned_at !== null || (loan.due_at && new Date(loan.due_at) < new Date())
}

onMounted(loadLoans)
</script>

<template>
  <div>
    <div class="header-row">
      <h2>Loans</h2>
      <button @click="loadLoans">REFRESH</button>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="loading">Loading…</p>

    <table v-if="!loading">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Book</th>
          <th>Loan Date / Due Date</th>
          <th>Return Date</th>
          <th><!-- actions --></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="loan in loans" :key="loan.id">
          <td>{{ loan.id }}</td>
          <td>{{ loan.user?.name ?? loan.user_name }}</td>
          <td>{{ loan.book?.title ?? loan.book_title }}</td>
          <td>
            <!-- existing loan date display -->
            <div>{{ loan.loaned_at_human ?? loan.loaned_at }}</div>
            <!-- new due date info -->
            <div class="due-label">
              {{ formatDueDisplay(loan) }}
            </div>
          </td>
          <td>
            {{ loan.returned_at_human ?? loan.returned_at ?? '-' }}
          </td>
          <td>
            <button
              class="icon-button"
              :disabled="isExtendDisabled(loan)"
              @click="openExtendDialog(loan)"
              title="Extend loan"
            >
              ⏩
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Extend Loan dialog -->
    <div v-if="isDialogOpen" class="dialog-backdrop">
      <div class="dialog">
        <h3>Extend Loan</h3>
        <p v-if="selectedLoan">
          Book: <strong>{{ selectedLoan.book?.title ?? selectedLoan.book_title }}</strong>
        </p>

        <label>
          Additional days:
          <select v-model.number="additionalDays">
            <option v-for="opt in options" :key="opt" :value="opt">
              {{ opt }} days
            </option>
          </select>
        </label>

        <p class="hint" v-if="selectedLoan && selectedLoan.due_at">
          New due date will be approximately
          {{
            new Date(
              new Date(selectedLoan.due_at).getTime() +
              additionalDays * 24 * 60 * 60 * 1000
            ).toLocaleString()
          }}
        </p>

        <div class="dialog-actions">
          <button @click="closeDialog">Cancel</button>
          <button @click="submitExtend">Submit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.icon-button {
  cursor: pointer;
}

.icon-button[disabled] {
  opacity: 0.4;
  cursor: not-allowed;
}

.due-label {
  font-size: 0.85rem;
  color: #666;
}

.error {
  color: red;
}

.dialog-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.dialog {
  background: #fff;
  padding: 1.5rem;
  border-radius: 4px;
  min-width: 300px;
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}

.hint {
  font-size: 0.8rem;
  color: #444;
  margin-top: 0.5rem;
}
</style>
