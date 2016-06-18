var webpack                 = require("webpack");
var ExtractTextPlugin       = require("extract-text-webpack-plugin");

module.exports = {
    entry: {
        dustpress_comments: __dirname + '/assets/dustpress-comments.js'
    },
    output: {
        path: __dirname + '/dist',
        filename: 'dustpress-comments.min.js'
    },
    module: {
        loaders: [
            {
                test: /\.css$/,
                loader: ExtractTextPlugin.extract("style", "css")
            },
        ]
    },
    plugins: [
        new ExtractTextPlugin("dustpress-comments.min.css", {
            allChunks: true
        })
    ],
    externals: {
        "jquery": "jQuery"
    }
};