const JavaScriptObfuscator = require('javascript-obfuscator');
const fs = require('fs');

const code = `
    // Kopyalanacak JavaScript kodu buraya
`;

const obfuscationResult = JavaScriptObfuscator.obfuscate(code, {
    compact: true,
    controlFlowFlattening: true,
    controlFlowFlatteningThreshold: 0.75,
    deadCodeInjection: true,
    deadCodeInjectionThreshold: 0.4,
    stringArray: true,
    stringArrayEncoding: ['base64'],
    stringArrayThreshold: 0.75
});

console.log(obfuscationResult.getObfuscatedCode());
