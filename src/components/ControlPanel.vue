<template>
  <div class="control-panel">
    <div class="panel-header">
      <h2>🎮 游戏控制</h2>
    </div>

    <div class="control-section">
      <div class="game-controls">
        <button 
          class="btn btn-primary" 
          @click="gameStore.startGame"
          :disabled="gameStore.gameStatus === 'playing'"
        >
          {{ gameStore.gameStatus === 'waiting' ? '开始游戏' : '重新开始' }}
        </button>
        
        <button 
          class="btn btn-secondary" 
          @click="gameStore.resetGame"
          :disabled="gameStore.gameStatus === 'waiting'"
        >
          重置游戏
        </button>
      </div>
    </div>

    <div class="control-section">
      <div class="difficulty-selector">
        <h3>🤖 AI难度</h3>
        <div class="difficulty-options">
          <label 
            class="difficulty-option"
            :class="{ active: gameStore.difficulty === 'easy' }"
          >
            <input 
              type="radio" 
              value="easy" 
              v-model="gameStore.difficulty"
              :disabled="gameStore.gameStatus === 'playing'"
            />
            <div class="option-content">
              <div class="option-icon">😊</div>
              <div class="option-info">
                <div class="option-name">简单</div>
                <div class="option-desc">随机落子</div>
              </div>
            </div>
          </label>
          
          <label 
            class="difficulty-option"
            :class="{ active: gameStore.difficulty === 'medium' }"
          >
            <input 
              type="radio" 
              value="medium" 
              v-model="gameStore.difficulty"
              :disabled="gameStore.gameStatus === 'playing'"
            />
            <div class="option-content">
              <div class="option-icon">🤔</div>
              <div class="option-info">
                <div class="option-name">中等</div>
                <div class="option-desc">混合策略</div>
              </div>
            </div>
          </label>
          
          <label 
            class="difficulty-option"
            :class="{ active: gameStore.difficulty === 'hard' }"
          >
            <input 
              type="radio" 
              value="hard" 
              v-model="gameStore.difficulty"
              :disabled="gameStore.gameStatus === 'playing'"
            />
            <div class="option-content">
              <div class="option-icon">🧠</div>
              <div class="option-info">
                <div class="option-name">困难</div>
                <div class="option-desc">最优策略</div>
              </div>
            </div>
          </label>
        </div>
      </div>
    </div>

    <div class="control-section">
      <div class="game-info">
        <div class="info-item">
          <span class="info-label">当前玩家:</span>
          <span class="info-value" :class="currentPlayerClass">
            {{ currentPlayerText }}
          </span>
        </div>
        <div class="info-item">
          <span class="info-label">游戏状态:</span>
          <span class="info-value" :class="gameStatusClass">
            {{ gameStatusText }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useGameStore } from '../stores/gameStore'

const gameStore = useGameStore()

// 当前玩家显示
const currentPlayerText = computed(() => {
  if (gameStore.gameStatus !== 'playing') return '-'
  return gameStore.isPlayerTurn ? '玩家 (X)' : 'AI (O)'
})

const currentPlayerClass = computed(() => {
  if (gameStore.gameStatus !== 'playing') return 'waiting'
  return gameStore.isPlayerTurn ? 'player' : 'ai'
})

// 游戏状态显示
const gameStatusText = computed(() => {
  switch (gameStore.gameStatus) {
    case 'waiting':
      return '等待开始'
    case 'playing':
      return '游戏中'
    case 'finished':
      return '游戏结束'
    default:
      return '未知状态'
  }
})

const gameStatusClass = computed(() => {
  return gameStore.gameStatus
})
</script>

<style scoped>
.control-panel {
  background: transparent;
  border-radius: 0;
  padding: 0;
  color: white;
  box-shadow: none;
  backdrop-filter: none;
  min-width: auto;
  width: 100%;
  height: 100%;
}

.panel-header {
  text-align: center;
  margin-bottom: 1.2rem;
}

.panel-header h2 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.control-section {
  margin-bottom: 1.2rem;
}

.control-section:last-child {
  margin-bottom: 0;
}

.game-controls {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn {
  padding: 0.6rem 1.2rem;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
}

.btn:not(:disabled):active {
  transform: translateY(0);
}

.btn-primary {
  background: linear-gradient(135deg, #2ecc71, #27ae60);
  color: white;
}

.btn-primary:not(:disabled):hover {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.btn-secondary {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
}

.btn-secondary:not(:disabled):hover {
  background: linear-gradient(135deg, #c0392b, #a93226);
}

.difficulty-selector h3 {
  margin: 0 0 0.8rem 0;
  font-size: 1rem;
  text-align: center;
  opacity: 0.9;
}

.difficulty-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.difficulty-option {
  display: block;
  cursor: pointer;
}

.difficulty-option input[type="radio"] {
  display: none;
}

.difficulty-option .option-content {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  padding: 0.6rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.difficulty-option:hover .option-content {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-1px);
}

.difficulty-option.active .option-content {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.option-icon {
  font-size: 1.2rem;
  opacity: 0.9;
}

.option-info {
  flex: 1;
}

.option-name {
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 0.1rem;
}

.option-desc {
  font-size: 0.7rem;
  opacity: 0.8;
}

.game-info {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  padding: 0.8rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-label {
  font-size: 0.8rem;
  opacity: 0.9;
}

.info-value {
  font-weight: 600;
  font-size: 0.8rem;
}

.info-value.player {
  color: #2ecc71;
}

.info-value.ai {
  color: #e74c3c;
}

.info-value.waiting {
  color: #f39c12;
}

.info-value.playing {
  color: #3498db;
}

.info-value.finished {
  color: #9b59b6;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .control-panel {
    min-width: 160px;
    padding: 0.8rem;
  }
  
  .panel-header h2 {
    font-size: 1.2rem;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
  }
  
  .option-name {
    font-size: 0.8rem;
  }
  
  .option-desc {
    font-size: 0.65rem;
  }
}
</style>  
