var webpack                 = require("webpack");
var ExtractTextPlugin       = require("extract-text-webpack-plugin");

module.exports = {
    entry: {
        dustpress_comments: __dirname + '/assets/dustpress-comments.js'
    },
    output: {
        path: __dirname + '/dist',
        filename: 'dustpress-comments.js'
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
        new ExtractTextPlugin("dustpress-comments.css", {
            allChunks: true
        }),
           new webpack.optimize.UglifyJsPlugin({
            include: /\.min\.js$/,
            minimize: true
        })
    ],
    externals: {
        "jquery": "jQuery"
    }
};