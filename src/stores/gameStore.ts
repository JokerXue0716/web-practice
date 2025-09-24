import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type Player = 'X' | 'O' | null
export type Board = Player[]
export type GameStatus = 'waiting' | 'playing' | 'finished'
export type Difficulty = 'easy' | 'medium' | 'hard'

export interface GameStats {
  totalGames: number
  playerWins: number
  aiWins: number
  draws: number
}

export const useGameStore = defineStore('game', () => {
  // 游戏状态
  const board = ref<Board>(Array(9).fill(null))
  const currentPlayer = ref<Player>('X')
  const gameStatus = ref<GameStatus>('waiting')
  const winner = ref<Player>(null)
  const difficulty = ref<Difficulty>('medium')

  // 统计状态
  const stats = ref<GameStats>({
    totalGames: 0,
    playerWins: 0,
    aiWins: 0,
    draws: 0
  })

  // 计算属性
  const isGameActive = computed(() => gameStatus.value === 'playing')
  const isPlayerTurn = computed(() => currentPlayer.value === 'X')
  const isAITurn = computed(() => currentPlayer.value === 'O')

  const winRate = computed(() => {
    if (stats.value.totalGames === 0) return 0
    return Math.round((stats.value.playerWins / stats.value.totalGames) * 100)
  })

  const aiWinRate = computed(() => {
    if (stats.value.totalGames === 0) return 0
    return Math.round((stats.value.aiWins / stats.value.totalGames) * 100)
  })

  const drawRate = computed(() => {
    if (stats.value.totalGames === 0) return 0
    return Math.round((stats.value.draws / stats.value.totalGames) * 100)
  })

  // 本地存储键名
  const STORAGE_KEY = 'tic-tac-toe-stats'

  // 检查获胜条件
  const checkWinner = (boardState: Board): Player => {
    const winPatterns = [
      [0, 1, 2], [3, 4, 5], [6, 7, 8], // 行
      [0, 3, 6], [1, 4, 7], [2, 5, 8], // 列
      [0, 4, 8], [2, 4, 6] // 对角线
    ]

    for (const pattern of winPatterns) {
      const [a, b, c] = pattern
      if (boardState[a] && boardState[a] === boardState[b] && boardState[a] === boardState[c]) {
        return boardState[a]
      }
    }

    return null
  }

  // 检查是否平局
  const isDraw = (boardState: Board): boolean => {
    return boardState.every(cell => cell !== null) && !checkWinner(boardState)
  }

  // 获取可用位置
  const getAvailableMoves = (boardState: Board): number[] => {
    return boardState.map((cell, index) => cell === null ? index : -1).filter(index => index !== -1)
  }

  // Minimax算法实现
  const minimax = (boardState: Board, depth: number, isMaximizing: boolean): number => {
    const winner = checkWinner(boardState)
    
    if (winner === 'O') return 10 - depth
    if (winner === 'X') return depth - 10
    if (isDraw(boardState)) return 0

    const availableMoves = getAvailableMoves(boardState)

    if (isMaximizing) {
      let bestScore = -Infinity
      for (const move of availableMoves) {
        const newBoard = [...boardState]
        newBoard[move] = 'O'
        const score = minimax(newBoard, depth + 1, false)
        bestScore = Math.max(score, bestScore)
      }
      return bestScore
    } else {
      let bestScore = Infinity
      for (const move of availableMoves) {
        const newBoard = [...boardState]
        newBoard[move] = 'X'
        const score = minimax(newBoard, depth + 1, true)
        bestScore = Math.min(score, bestScore)
      }
      return bestScore
    }
  }

  // 获取AI最佳移动
  const getBestMove = (boardState: Board): number => {
    const availableMoves = getAvailableMoves(boardState)
    
    if (availableMoves.length === 0) return -1

    switch (difficulty.value) {
      case 'easy':
        // 简单模式：随机选择
        return availableMoves[Math.floor(Math.random() * availableMoves.length)]
      
      case 'medium': {
        // 中等模式：50%概率使用最佳策略，50%随机
        if (Math.random() < 0.5) {
          return availableMoves[Math.floor(Math.random() * availableMoves.length)]
        }
        // 继续执行困难模式逻辑
        // eslint-disable-next-line no-fallthrough
      }
        
      case 'hard': {
        // 困难模式：使用Minimax算法
        let bestScore = -Infinity
        let bestMove = availableMoves[0]
        
        for (const move of availableMoves) {
          const newBoard = [...boardState]
          newBoard[move] = 'O'
          const score = minimax(newBoard, 0, false)
          if (score > bestScore) {
            bestScore = score
            bestMove = move
          }
        }
        return bestMove
      }
        
      default:
        return availableMoves[0]
    }
  }

  // AI落子
  const makeAIMove = () => {
    if (gameStatus.value !== 'playing' || currentPlayer.value !== 'O') {
      return
    }

    const bestMove = getBestMove(board.value)
    if (bestMove !== -1) {
      board.value[bestMove] = 'O'
      
      const gameWinner = checkWinner(board.value)
      if (gameWinner) {
        winner.value = gameWinner
        gameStatus.value = 'finished'
        recordGameResult(gameWinner)
        return
      }

      if (isDraw(board.value)) {
        gameStatus.value = 'finished'
        recordGameResult(null)
        return
      }

      currentPlayer.value = 'X'
    }
  }

  // 玩家落子
  const makeMove = (position: number): boolean => {
    if (board.value[position] !== null || gameStatus.value !== 'playing' || currentPlayer.value !== 'X') {
      return false
    }

    board.value[position] = 'X'
    
    const gameWinner = checkWinner(board.value)
    if (gameWinner) {
      winner.value = gameWinner
      gameStatus.value = 'finished'
      recordGameResult(gameWinner)
      return true
    }

    if (isDraw(board.value)) {
      gameStatus.value = 'finished'
      recordGameResult(null)
      return true
    }

    currentPlayer.value = 'O'
    
    // AI回合
    setTimeout(() => {
      if (gameStatus.value === 'playing') {
        makeAIMove()
      }
    }, 500)

    return true
  }

  // 开始游戏
  const startGame = () => {
    board.value = Array(9).fill(null)
    currentPlayer.value = 'X'
    gameStatus.value = 'playing'
    winner.value = null
  }

  // 重置游戏
  const resetGame = () => {
    board.value = Array(9).fill(null)
    currentPlayer.value = 'X'
    gameStatus.value = 'waiting'
    winner.value = null
  }

  // 设置难度
  const setDifficulty = (newDifficulty: Difficulty) => {
    difficulty.value = newDifficulty
  }

  // 加载统计数据
  const loadStats = () => {
    try {
      const savedStats = localStorage.getItem(STORAGE_KEY)
      if (savedStats) {
        const parsedStats = JSON.parse(savedStats)
        stats.value = {
          totalGames: parsedStats.totalGames || 0,
          playerWins: parsedStats.playerWins || 0,
          aiWins: parsedStats.aiWins || 0,
          draws: parsedStats.draws || 0
        }
      }
    } catch (error) {
      console.error('加载统计数据失败:', error)
      resetStats()
    }
  }

  // 保存统计数据
  const saveStats = () => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(stats.value))
    } catch (error) {
      console.error('保存统计数据失败:', error)
    }
  }

  // 记录游戏结果
  const recordGameResult = (gameWinner: Player) => {
    stats.value.totalGames++
    
    if (gameWinner === 'X') {
      stats.value.playerWins++
    } else if (gameWinner === 'O') {
      stats.value.aiWins++
    } else {
      stats.value.draws++
    }
    
    saveStats()
  }

  // 重置统计数据
  const resetStats = () => {
    stats.value = {
      totalGames: 0,
      playerWins: 0,
      aiWins: 0,
      draws: 0
    }
    saveStats()
  }

  // 初始化时加载数据
  loadStats()

  return {
    // 游戏状态
    board,
    currentPlayer,
    gameStatus,
    winner,
    difficulty,
    
    // 统计状态
    stats,
    
    // 计算属性
    isGameActive,
    isPlayerTurn,
    isAITurn,
    winRate,
    aiWinRate,
    drawRate,
    
    // 游戏方法
    makeMove,
    startGame,
    resetGame,
    setDifficulty,
    checkWinner,
    isDraw,
    
    // 统计方法
    recordGameResult,
    resetStats,
    loadStats,
    saveStats
  }
})