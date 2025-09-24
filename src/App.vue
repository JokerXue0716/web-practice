<template>
  <div id="app">
    <header class="app-header">
      <h1 class="app-title">
        <span class="title-icon">🎯</span>
        智能井字棋
        <span class="title-subtitle">AI挑战赛</span>
      </h1>
    </header>

    <main class="app-main">
      <div class="game-container">
        <!-- 左侧棋盘区域 -->
        <div class="board-section">
          <GameBoard ref="gameBoardRef" />
        </div>

        <!-- 右侧控制和统计区域 -->
        <div class="right-panel">
          <!-- 上排：三个小面板 -->
          <div class="top-row">
            <div class="mini-panel game-control-mini">
              <h4>🎮 游戏控制</h4>
              <div class="control-buttons">
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
            
            <div class="mini-panel ai-difficulty-mini">
              <h4>🤖 AI难度</h4>
              <div class="difficulty-options">
                <label class="difficulty-option" :class="{ active: gameStore.difficulty === 'easy' }">
                  <input type="radio" value="easy" v-model="gameStore.difficulty" :disabled="gameStore.gameStatus === 'playing'" />
                  <span>😊 简单</span>
                </label>
                <label class="difficulty-option" :class="{ active: gameStore.difficulty === 'medium' }">
                  <input type="radio" value="medium" v-model="gameStore.difficulty" :disabled="gameStore.gameStatus === 'playing'" />
                  <span>🤔 中等</span>
                </label>
                <label class="difficulty-option" :class="{ active: gameStore.difficulty === 'hard' }">
                  <input type="radio" value="hard" v-model="gameStore.difficulty" :disabled="gameStore.gameStatus === 'playing'" />
                  <span>🧠 困难</span>
                </label>
              </div>
            </div>
            
            <div class="mini-panel player-status-mini">
              <h4>👤 玩家状态</h4>
              <div class="status-info">
                <div class="status-item">
                  <span class="status-label">当前玩家:</span>
                  <span class="status-value" :class="currentPlayerClass">{{ currentPlayerText }}</span>
                </div>
                <div class="status-item">
                  <span class="status-label">游戏状态:</span>
                  <span class="status-value" :class="gameStatusClass">{{ gameStatusText }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- 下排：两个统计面板 -->
          <div class="bottom-row">
            <div class="stats-panel game-stats-panel">
              <h4>📊 游戏统计</h4>
              <div class="stats-grid">
                <div class="stat-item">
                  <div class="stat-icon">🎮</div>
                  <div class="stat-info">
                    <div class="stat-value">{{ gameStore.stats.totalGames }}</div>
                    <div class="stat-label">总局数</div>
                  </div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">🏆</div>
                  <div class="stat-info">
                    <div class="stat-value">{{ gameStore.stats.playerWins }}</div>
                    <div class="stat-label">玩家胜利</div>
                  </div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">🤖</div>
                  <div class="stat-info">
                    <div class="stat-value">{{ gameStore.stats.aiWins }}</div>
                    <div class="stat-label">AI胜利</div>
                  </div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">🤝</div>
                  <div class="stat-info">
                    <div class="stat-value">{{ gameStore.stats.draws }}</div>
                    <div class="stat-label">平局</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="stats-panel win-rate-panel">
              <h4>📈 胜率统计</h4>
              <div class="win-rate-content">
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
                <button 
                  class="btn-reset" 
                  @click="gameStore.resetStats"
                  :disabled="!hasStats"
                  title="重置统计"
                >
                  🔄 重置统计
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="app-footer">
      <p>使用 Vue 3 + TypeScript + Minimax算法开发 | 挑战AI的智慧</p>
    </footer>

    <!-- 背景装饰 -->
    <div class="background-decoration">
      <div class="decoration-circle circle-1"></div>
      <div class="decoration-circle circle-2"></div>
      <div class="decoration-circle circle-3"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import GameBoard from './components/GameBoard.vue'
import { useGameStore } from './stores/gameStore'

// 游戏棋盘引用
const gameBoardRef = ref<InstanceType<typeof GameBoard> | null>(null)
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

<style>
/* 全局样式重置 */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: #2c3e50;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  overflow-x: hidden;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
}

.app-header {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding: 1rem 0;
  text-align: center;
  position: relative;
  z-index: 10;
}

.app-title {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.title-icon {
  font-size: 2.2rem;
  animation: bounce 2s infinite;
}

.title-subtitle {
  font-size: 0.9rem;
  font-weight: 400;
  opacity: 0.8;
  background: linear-gradient(45deg, #ff6b6b, #feca57);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.app-main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  position: relative;
  z-index: 5;
}

.game-container {
  display: flex;
  gap: 2rem;
  align-items: stretch;
  max-width: 1200px;
  width: 100%;
  height: calc(100vh - 200px);
  min-height: 600px;
}

.board-section {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 400px;
}

.right-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-width: 600px;
}

.top-row {
  display: flex;
  gap: 1rem;
  height: 45%;
}

.bottom-row {
  display: flex;
  gap: 1rem;
  height: 55%;
}

.mini-panel {
  flex: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 1rem;
  color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
}

.mini-panel h4 {
  margin: 0 0 0.8rem 0;
  font-size: 1rem;
  font-weight: 600;
  text-align: center;
  opacity: 0.9;
}

.stats-panel {
  flex: 1;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 12px;
  padding: 1rem;
  color: white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
}

.stats-panel h4 {
  margin: 0 0 0.8rem 0;
  font-size: 1rem;
  font-weight: 600;
  text-align: center;
  opacity: 0.9;
}

/* 游戏控制面板样式 */
.control-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
  flex: 1;
  justify-content: center;
}

.btn {
  padding: 0.6rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #2ecc71, #27ae60);
  color: white;
}

.btn-secondary {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
}

/* AI难度面板样式 */
.difficulty-options {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex: 1;
  justify-content: center;
}

.difficulty-option {
  display: block;
  cursor: pointer;
}

.difficulty-option input[type="radio"] {
  display: none;
}

.difficulty-option span {
  display: block;
  padding: 0.4rem 0.6rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
  text-align: center;
  font-size: 0.8rem;
}

.difficulty-option:hover span {
  background: rgba(255, 255, 255, 0.15);
}

.difficulty-option.active span {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
}

/* 玩家状态面板样式 */
.status-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.6rem;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-label {
  font-size: 0.8rem;
  opacity: 0.9;
}

.status-value {
  font-weight: 600;
  font-size: 0.8rem;
}

.status-value.player {
  color: #2ecc71;
}

.status-value.ai {
  color: #e74c3c;
}

.status-value.waiting {
  color: #f39c12;
}

.status-value.playing {
  color: #3498db;
}

.status-value.finished {
  color: #9b59b6;
}

/* 游戏统计面板样式 */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.6rem;
  flex: 1;
}

.stat-item {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  padding: 0.6rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.stat-icon {
  font-size: 1.2rem;
  opacity: 0.9;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.1rem;
}

.stat-label {
  font-size: 0.65rem;
  opacity: 0.8;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* 胜率统计面板样式 */
.win-rate-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.8rem;
}

.rate-item {
  margin-bottom: 0.6rem;
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

.btn-reset {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.3s ease;
  color: white;
  font-weight: 600;
}

.btn-reset:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
}

.btn-reset:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.app-footer {
  background: rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1rem 0;
  text-align: center;
  position: relative;
  z-index: 10;
}

.app-footer p {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
}

.background-decoration {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

.decoration-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  animation: float 6s ease-in-out infinite;
}

.circle-1 {
  width: 200px;
  height: 200px;
  top: 10%;
  left: 10%;
  animation-delay: 0s;
}

.circle-2 {
  width: 150px;
  height: 150px;
  top: 60%;
  right: 15%;
  animation-delay: 2s;
}

.circle-3 {
  width: 100px;
  height: 100px;
  bottom: 20%;
  left: 20%;
  animation-delay: 4s;
}

/* 动画定义 */
@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
    opacity: 0.5;
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
    opacity: 0.8;
  }
}

/* 响应式设计 */
@media (max-width: 1024px) {
  .game-container {
    flex-direction: column;
    height: auto;
    min-height: auto;
  }
  
  .board-section {
    min-width: auto;
    order: 1;
  }
  
  .right-panel {
    min-width: auto;
    order: 2;
  }
  
  .top-row, .bottom-row {
    flex-direction: column;
    height: auto;
  }
}
</style>