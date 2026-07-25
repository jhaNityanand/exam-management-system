/**
 * Compatibility facade — exam rule enforcement lives in ./rules/*.
 */
export { bindProctoring, createRuleEngine, startWebcamMonitor } from './rules/RuleEngine';
export { createDevtoolsWatcher, isDevtoolsLikelyOpen } from './rules/devtoolsDetect';
