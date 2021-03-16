module.exports = function(grunt) {

    // Load any plugins -- must be installed via npm install and specified in package.json

    grunt.loadNpmTasks('grunt-babel');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-modernizr');
    grunt.loadNpmTasks('grunt-newer');
    grunt.loadNpmTasks('grunt-sftp-deploy');

    grunt.registerTask('default', ['sass', 'jshint', 'babel']);

    // Project configuration.
    grunt.initConfig({
        // reads package.json to import metadata
        pkg: grunt.file.readJSON('package.json'),

        babel: {
            options: {
                sourceMap: true,
                presets: ['@babel/preset-env']
            },
            dist: {
                files: {
                    'dist/js/accordions.js': 'js/accordions.js',
                    'dist/js/animations.js': 'js/animations.js',
                    'dist/js/global.js': 'js/global.js',
                    'dist/js/takeover.js': 'js/takeover.js',
                    'dist/js/throbber.js': 'js/throbber.js',
                    'dist/js/views.js': 'js/views.js',
                    'dist/js/yearpicker.js': 'js/yearpicker.js'
                }
            }
        },

        cssmin:{
            options:{
                sourceMap: true
            },
            my_target:{
                files: [{
                    expand: true,
                    cwd: 'dist/css/',
                    src: ['*.css', '!*.min.css'],
                    dest: 'dist/css/',
                    ext: '.min.css'
                }]
            }
        },

        jshint: {
            options: {
                'esversion': 6
            },
            all: ['Gruntfile.js', 'js/*.js'],
        },

        modernizr: {
            dist: {
              "crawl": false,
              "customTests": [],
              "dest": "dist/js/modernizr-output.js",
              "tests": [
                "svg",
                "video",
                "cssanimations"
              ],
              "options": [
                "setClasses"
              ],
              "uglify": true
            },
        },

        sass: {
            dist: {
                files: {
                    'dist/css/style.css' : 'sass/style.scss',
                    'dist/css/print-style.css' : 'sass/print-style.scss'
                }
            },
        },

        "sftp-deploy": {
            build: {
                auth: {
                  host: 'weavercoop.online',
                  // port: 21,
                  authKey: 'key1',
                  forceVerbose: true,
                },
                src: 'D:/sites/weaver/weaver-lrb/web/themes/custom/weaver-lrb/dist',
                dest: '/home/weaverdev/lrb/web/themes/custom/weaver-lrb/dist'
            }
        },

        watch: {
            sass:{
                files: ['sass/*.scss'],
                tasks: ['sass:dist', 'sftp-deploy']
            },
            js:{
                files: ['js/*.js'],
                tasks: ['jshint', 'babel', 'sftp-deploy']
            },
        }

    });

    // Event handling
    grunt.event.on('watch', function(action, filepath){

    });
};
