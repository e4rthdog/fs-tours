import { execSync } from 'child_process'
import { writeFileSync } from 'fs'

const getGitVersion = () => {
  try {
    return execSync('git describe --tags --long --dirty --always').toString().trim()
  } catch {
    return 'unknown'
  }
}

const version = getGitVersion()
const content = `export const APP_VERSION = '${version}'\n`

writeFileSync('./src/version.js', content)
console.log(`Version generated: ${version}`)
