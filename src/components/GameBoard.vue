<template>
  <div class="game-board">
    <div class="board-container">
      <div 
        v-for="(cell, index) in gameStore.board" 
        :key="index"
        class="cell"
        :class="{
          'cell-x': cell === 'X',
          'cell-o': cell === 'O',
          'cell-clickable': cell === null && gameStore.isPlayerTurn && gameStore.isGameActive,
          'cell-winning': isWinningCell(index)
        }"
        @click="handleCellClick(index)"
      >
        <span v-if="cell" class="cell-content">{{ cell }}</span>
      </div>
    </div>
    
    <!-- 游戏状态显示 -->
    <div class="game-status">
      <div v-if="gameStore.gameStatus === 'waiting'" class="status-message">
        点击"开始游戏"开始新游戏
      </div>
      <div v-else-if="gameStore.gameStatus === 'playing'" class="status-message">
        <span v-if="gameStore.isPlayerTurn">轮到你了 (X)</span>
        <span v-else class="ai-thinking">AI思考中... (O)</span>
      </div>
      <div v-else-if="gameStore.gameStatus === 'finished'" class="status-message game-over">
        <span v-if="gameStore.winner === 'X'" class="player-win">🎉 你赢了！</span>
        <span v-else-if="gameStore.winner === 'O'" class="ai-win">🤖 AI获胜！</span>
        <span v-else class="draw">🤝 平局！</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useGameStore } from '../stores/gameStore'

// 使用游戏store
const gameStore = useGameStore()

// 获胜位置
const winningPositions = computed(() => {
  const winPatterns = [
    [0, 1, 2], [3, 4, 5], [6, 7, 8], // 行
    [0, 3, 6], [1, 4, 7], [2, 5, 8], // 列
    [0, 4, 8], [2, 4, 6] // 对角线
  ]

  for (const pattern of winPatterns) {
    const [a, b, c] = pattern
    if (gameStore.board[a] && gameStore.board[a] === gameStore.board[b] && gameStore.board[a] === gameStore.board[c]) {
      return pattern
    }
  }
  return []
})

// 检查是否为获胜位置
const isWinningCell = (index: number): boolean => {
  return winningPositions.value.includes(index)
}

// 处理单元格点击
const handleCellClick = (index: number) => {
  if (gameStore.gameStatus === 'playing' && gameStore.isPlayerTurn) {
    gameStore.makeMove(index)
  }
}
</script>

<style scoped>
.game-board {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

.board-container {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: repeat(3, 1fr);
  gap: 4px;
  background-color: #2c3e50;
  padding: 4px;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.cell {
  width: 100px;
  height: 100px;
  background-color: #ecf0f1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: bold;
  border-radius: 8px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.cell::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
  transform: translateX(-100%);
  transition: transform 0.6s;
}

.cell:hover::before {
  transform: translateX(100%);
}

.cell-clickable {
  cursor: pointer;
}

.cell-clickable:hover {
  background-color: #d5dbdb;
  transform: scale(1.05);
}

.cell-x {
  background-color: #e8f5e8;
  color: #27ae60;
}

.cell-x .cell-content {
  animation: popIn 0.3s ease-out;
}

.cell-o {
  background-color: #fdf2e9;
  color: #e67e22;
}

.cell-o .cell-content {
  animation: popIn 0.3s ease-out;
}

.cell-winning {
  background-color: #fff3cd !important;
  animation: pulse 1s infinite;
  box-shadow: 0 0 20px rgba(255, 193, 7, 0.6);
}

.game-status {
  text-align: center;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-message {
  font-size: 1.2rem;
  font-weight: 600;
  padding: 1rem 2rem;
  border-radius: 25px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.status-message.game-over {
  font-size: 1.4rem;
  animation: bounceIn 0.6s ease-out;
}

.ai-thinking {
  animation: pulse 1.5s infinite;
}

.player-win {
  color: #2ecc71;
}

.ai-win {
  color: #e74c3c;
}

.draw {
  color: #f39c12;
}

/* 动画定义 */
@keyframes popIn {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

@keyframes bounceIn {
  0% {
    transform: scale(0.3);
    opacity: 0;
  }
  50% {
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* 响应式设计 */
@media (max-width: 768px) {
  .cell {
    width: 80px;
    height: 80px;
    font-size: 2rem;
  }
  
  .status-message {
    font-size: 1rem;
    padding: 0.8rem 1.5rem;
  }
  
  .status-message.game-over {
    font-size: 1.2rem;
  }
}

@media (max-width: 480px) {
  .cell {
    width: 70px;
    height: 70px;
    font-size: 1.8rem;
  }
  
  .board-container {
    gap: 3px;
    padding: 3px;
  }
}
</style>