<template>
  <div class="game-stats">
    <div class="stats-header">
      <h2>📊 游戏统计</h2>
      <button 
        class="btn-reset" 
        @click="gameStore.resetStats"
        :disabled="!hasStats"
        title="重置统计"
      >
        🔄
      </button>
    </div>

    <div v-if="hasStats" class="stats-content">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🎮</div>
          <div class="stat-info">
            <div class="stat-value">{{ gameStore.stats.totalGames }}</div>
            <div class="stat-label">总局数</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">🏆</div>
          <div class="stat-info">
            <div class="stat-value">{{ gameStore.stats.playerWins }}</div>
            <div class="stat-label">玩家胜利</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">🤖</div>
          <div class="stat-info">
            <div class="stat-value">{{ gameStore.stats.aiWins }}</div>
            <div class="stat-label">AI胜利</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">🤝</div>
          <div class="stat-info">
            <div class="stat-value">{{ gameStore.stats.draws }}</div>
            <div class="stat-label">平局</div>
          </div>
        </div>
      </div>

      <div class="win-rates">
        <h3>胜率统计</h3>
        <div class="rate-item">
          <div class="rate-info">
            <span class="rate-label">玩家胜率</span>
            <span class="rate-value">{{ playerWinRate }}%</span>
          </div>
          <div class="rate-bar">
            <div class="rate-fill player" :style="{ width: playerWinRate + '%' }"></div>
          </div>
        </div>
        
        <div class="rate-item">
          <div class="rate-info">
            <span class="rate-label">AI胜率</span>
            <span class="rate-value">{{ aiWinRate }}%</span>
          </div>
          <div class="rate-bar">
            <div class="rate-fill ai" :style="{ width: aiWinRate + '%' }"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="no-data">
      <div class="no-data-icon">📈</div>
      <div class="no-data-text">暂无游戏数据</div>
      <div class="no-data-hint">开始游戏来查看统计信息</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useGameStore } from '../stores/gameStore'

const gameStore = useGameStore()

// 是否有统计数据
const hasStats = computed(() => {
  return gameStore.stats.totalGames > 0
})

// 玩家胜率
const playerWinRate = computed(() => {
  if (gameStore.stats.totalGames === 0) return 0
  return Math.round((gameStore.stats.playerWins / gameStore.stats.totalGames) * 100)
})

// AI胜率
const aiWinRate = computed(() => {
  if (gameStore.stats.totalGames === 0) return 0
  return Math.round((gameStore.stats.aiWins / gameStore.stats.totalGames) * 100)
})
</script>

<style scoped>
.game-stats {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 12px;
  padding: 1.2rem;
  color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  min-width: 280px;
}

.stats-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.2rem;
}

.stats-header h2 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.btn-reset {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-reset:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(180deg);
}

.btn-reset:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.8rem;
  margin-bottom: 1.2rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  transition: all 0.3s ease;
}

.stat-card:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.stat-icon {
  font-size: 1.5rem;
  opacity: 0.9;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.2rem;
}

.stat-label {
  font-size: 0.7rem;
  opacity: 0.8;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.win-rates h3 {
  margin: 0 0 0.8rem 0;
  font-size: 1rem;
  text-align: center;
  opacity: 0.9;
}

.rate-item {
  margin-bottom: 0.8rem;
}

.rate-item:last-child {
  margin-bottom: 0;
}

.rate-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.3rem;
}

.rate-label {
  font-size: 0.8rem;
  opacity: 0.9;
}

.rate-value {
  font-size: 0.8rem;
  font-weight: 600;
}

.rate-bar {
  height: 6px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
}

.rate-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.8s ease;
}

.rate-fill.player {
  background: linear-gradient(90deg, #2ecc71, #27ae60);
}

.rate-fill.ai {
  background: linear-gradient(90deg, #e74c3c, #c0392b);
}

.no-data {
  text-align: center;
  padding: 1.5rem 0;
}

.no-data-icon {
  font-size: 2.5rem;
  margin-bottom: 0.8rem;
  opacity: 0.7;
}

.no-data-text {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.3rem;
  opacity: 0.9;
}

.no-data-hint {
  font-size: 0.8rem;
  opacity: 0.7;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .game-stats {
    min-width: 250px;
    padding: 1rem;
  }
  
  .stats-header h2 {
    font-size: 1.2rem;
  }
  
  .stats-grid {
    gap: 0.6rem;
  }
  
  .stat-card {
    padding: 0.6rem;
  }
  
  .stat-value {
    font-size: 1.2rem;
  }
  
  .stat-label {
    font-size: 0.65rem;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .game-stats {
    min-width: 200px;
  }
}
</style>