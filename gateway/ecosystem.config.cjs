module.exports = {
  apps: [{
    name: 'ias4u-wa-gateway',
    script: 'src/index.js',
    cwd: '/opt/wa-gateway',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1200M',
    env: { NODE_ENV: 'production' }
  }]
};
