import { ref, computed } from 'vue'

export type Player = 'X' | 'O' | null
export type Board = Player[]
export type GameStatus = 'waiting' | 'playing' | 'finished'
export type Difficulty = 'easy' | 'medium' | 'hard'

export interface GameState {
  board: Board
  currentPlayer: Player
  gameStatus: GameStatus
  winner: Player
  difficulty: Difficulty
}

export function useGameLogic() {
  // 游戏状态
  const board = ref<Board>(Array(9).fill(null))
  const currentPlayer = ref<Player>('X')
  const gameStatus = ref<GameStatus>('waiting')
  const winner = ref<Player>(null)
  const difficulty = ref<Difficulty>('medium')

  // 计算属性
  const isGameActive = computed(() => gameStatus.value === 'playing')
  const isPlayerTurn = computed(() => currentPlayer.value === 'X')
  const isAITurn = computed(() => currentPlayer.value === 'O')

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
      return true
    }

    if (isDraw(board.value)) {
      gameStatus.value = 'finished'
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
        return
      }

      if (isDraw(board.value)) {
        gameStatus.value = 'finished'
        return
      }

      currentPlayer.value = 'X'
    }
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

  return {
    // 状态
    board,
    currentPlayer,
    gameStatus,
    winner,
    difficulty,
    
    // 计算属性
    isGameActive,
    isPlayerTurn,
    isAITurn,
    
    // 方法
    makeMove,
    startGame,
    resetGame,
    setDifficulty,
    checkWinner,
    isDraw
  }
}