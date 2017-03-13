module.exports = function(grunt) {

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    /**
     * Remove bundled and bundling files
     */
    clean.ltiproctoringbundle = [out];

    /**
     * Compile tao files into a bundle
     */
    requirejs.ltiproctoringbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : {
                'ltiProctoring' : root + '/ltiProctoring/views/js',
                'taoProctoring' : root + '/taoProctoring/views/js'
            },
            modules : [{
                name: 'ltiProctoring/controller/routes',
                include : ext.getExtensionsControllers(['ltiProctoring']),
                exclude: ['mathJax'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.ltiproctoringbundle = {
        files: [
            { src: [out + '/ltiProctoring/controller/routes.js'],  dest: root + '/ltiProctoring/views/js/controllers.min.js' },
            { src: [out + '/ltiProctoring/controller/routes.js.map'],  dest: root + '/ltiProctoring/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('ltiproctoringbundle', ['clean:ltiproctoringbundle', 'requirejs:ltiproctoringbundle', 'copy:ltiproctoringbundle']);
};
