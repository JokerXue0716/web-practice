import axios from 'axios'

const API_URL = 'http://localhost:11434/api/chat'

/**
 * 调用本地Ollama API进行AI润色
 * @param {string} text 需要润色的文本
 * @returns {Promise<string>} 润色后的文本
 */
export async function aiPolish(text) {
  const body = {
    model: 'qwen2', // 推荐用支持中文的模型
    messages: [
      { role: 'system', content: '你是一个专业的技术写作助手，请对用户输入的技术内容进行语言润色和表达优化，保持原意不变。请用中文输出。' },
      { role: 'user', content: text }
    ],
    stream: false
  }
  const resp = await axios.post(API_URL, body)
  // Ollama 返回格式
  return resp.data.message?.content || ''
}

/**
 * 代码漏洞检测
 */
export async function aiCodeCheck(text) {
  const body = {
    model: 'qwen2',
    messages: [
      { role: 'system', content: '你是一个资深代码安全专家，请帮我检测下面的代码或技术描述中是否存在安全漏洞或不规范之处，并用中文详细说明。' },
      { role: 'user', content: text }
    ],
    stream: false
  }
  const resp = await axios.post(API_URL, body)
  return resp.data.message?.content || ''
}

/**
 * 自动生成摘要
 */
export async function aiSummary(text) {
  const body = {
    model: 'qwen2',
    messages: [
      { role: 'system', content: '请用中文为下面的技术内容生成简明扼要的摘要，突出重点。' },
      { role: 'user', content: text }
    ],
    stream: false
  }
  const resp = await axios.post(API_URL, body)
  return resp.data.message?.content || ''
}

/**
 * 智能标签推荐
 */
export async function aiTagRecommend(text) {
  const body = {
    model: 'qwen2',
    messages: [
      { role: 'system', content: '请根据下面的技术内容，推荐3-5个相关的中文标签，标签之间用逗号分隔。' },
      { role: 'user', content: text }
    ],
    stream: false
  }
  const resp = await axios.post(API_URL, body)
  return resp.data.message?.content || ''
}
