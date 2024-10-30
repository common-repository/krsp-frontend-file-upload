var cssnext = require('cssnext')
var autoprefixer = require('autoprefixer')
module.exports = {
  // configure a built-in compiler
  sass: {
    includePaths: ["node_modules"]
  },
  // provide your own postcss plugins
  // postcss: [...],
  postcss: [cssnext(), autoprefixer()],
  // register custom compilers
  customCompilers: {
  }
}