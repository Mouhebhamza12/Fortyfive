<script setup>
import { computed, onMounted, ref } from "vue";
import { apiFetch, authJsonHeaders } from "../lib/api";

const feedbacks = ref([]);
const isLoading = ref(true);
const filterStatus = ref("all");

onMounted(async () => {
  await fetchFeedbacks();
});

const fetchFeedbacks = async () => {
  try {
    const response = await apiFetch("/admin/feedback");
    feedbacks.value = response.ok ? await response.json() : [];
  } catch {
    feedbacks.value = [];
  } finally {
    isLoading.value = false;
  }
};

const filteredFeedbacks = computed(() => {
  if (filterStatus.value === "all") {
    return feedbacks.value;
  }

  return feedbacks.value.filter((entry) => entry.status === filterStatus.value);
});

const updateStatus = async (feedback, status) => {
  try {
    const response = await apiFetch(`/admin/feedback/${feedback.id}`, {
      method: "PUT",
      headers: authJsonHeaders,
      body: JSON.stringify({ status }),
    });

    if (response.ok) {
      await fetchFeedbacks();
    }
  } catch (error) {
    console.error("Error updating feedback:", error);
  }
};

const deleteFeedback = async (feedback) => {
  if (!confirm("Delete this feedback?")) {
    return;
  }

  try {
    const response = await apiFetch(`/admin/feedback/${feedback.id}`, {
      method: "DELETE",
    });

    if (response.ok) {
      await fetchFeedbacks();
    }
  } catch (error) {
    console.error("Error deleting feedback:", error);
  }
};

const formatDate = (value) =>
  new Date(value).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
</script>

<template>
  <div class="feedback-admin">
    <section class="page-hero">
      <div>
        <p class="eyebrow">Customer voice</p>
        <h2 class="page-title">Feedback</h2>
        <p class="page-subtitle">
          Read customer messages, ratings, and follow up on issues quickly.
        </p>
      </div>
    </section>

    <section class="filter-strip">
      <button
        v-for="status in ['all', 'new', 'read', 'resolved']"
        :key="status"
        type="button"
        class="filter-chip"
        :class="{ active: filterStatus === status }"
        @click="filterStatus = status"
      >
        {{ status === "all" ? "All" : status.charAt(0).toUpperCase() + status.slice(1) }}
      </button>
    </section>

    <section class="panel">
      <div v-if="isLoading" class="loading-state">Loading feedback...</div>
      <div v-else-if="!filteredFeedbacks.length" class="empty-state">No feedback yet.</div>

      <div v-else class="feedback-list">
        <article v-for="entry in filteredFeedbacks" :key="entry.id" class="feedback-item">
          <div class="feedback-head">
            <div>
              <h3>{{ entry.name }}</h3>
              <p>{{ formatDate(entry.created_at) }}</p>
            </div>
            <span class="status-badge">{{ entry.status }}</span>
          </div>

          <p class="feedback-meta">
            <span v-if="entry.email">{{ entry.email }}</span>
            <span v-if="entry.phone"> · {{ entry.phone }}</span>
            <span v-if="entry.rating"> · {{ entry.rating }}/5</span>
          </p>

          <p class="feedback-message">{{ entry.message }}</p>

          <div class="feedback-actions">
            <button
              v-for="status in ['new', 'read', 'resolved']"
              :key="status"
              type="button"
              class="action-btn"
              :disabled="entry.status === status"
              @click="updateStatus(entry, status)"
            >
              {{ status }}
            </button>
            <button type="button" class="action-btn danger" @click="deleteFeedback(entry)">
              Delete
            </button>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.feedback-admin {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  color: #f3ead8;
}

.page-hero,
.panel,
.feedback-item {
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(180deg, rgba(28, 28, 28, 0.98), rgba(20, 20, 20, 0.98));
}

.page-hero,
.panel {
  border-radius: 1.15rem;
  padding: 1.45rem 1.5rem;
}

.eyebrow {
  margin: 0 0 0.45rem;
  font-size: 0.76rem;
  color: #90b6c6;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.page-title {
  margin: 0;
  font-size: 1.95rem;
}

.page-subtitle {
  margin: 0.75rem 0 0;
  color: #aaa497;
  max-width: 38rem;
  line-height: 1.55;
}

.filter-strip {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.filter-chip,
.action-btn {
  padding: 0.72rem 0.95rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: rgba(255, 255, 255, 0.03);
  color: #d6cdbb;
  cursor: pointer;
  font: inherit;
}

.filter-chip.active {
  border-color: rgba(255, 255, 255, 0.14);
}

.feedback-list {
  display: grid;
  gap: 1rem;
}

.feedback-item {
  border-radius: 1rem;
  padding: 1rem;
}

.feedback-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}

.feedback-head h3,
.feedback-message {
  margin: 0;
}

.feedback-head p,
.feedback-meta {
  margin: 0.25rem 0 0;
  color: #8b867d;
  font-size: 0.85rem;
}

.feedback-message {
  margin-top: 0.85rem;
  line-height: 1.6;
}

.feedback-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-top: 1rem;
}

.action-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.action-btn.danger {
  color: #f0a39c;
}

.status-badge,
.loading-state,
.empty-state {
  display: inline-flex;
  align-items: center;
  padding: 0.36rem 0.72rem;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.06);
  text-transform: capitalize;
}

.loading-state,
.empty-state {
  min-height: 10rem;
  justify-content: center;
  width: 100%;
  color: #908b82;
}

@media (max-width: 768px) {
  .page-hero,
  .panel {
    padding: 1.15rem;
  }

  .page-title {
    font-size: 1.55rem;
  }

  .filter-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .filter-chip {
    width: 100%;
    text-align: center;
  }

  .feedback-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .feedback-meta,
  .feedback-message {
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  .feedback-actions {
    flex-direction: column;
  }

  .feedback-actions .action-btn {
    width: 100%;
    text-align: center;
  }
}
</style>
