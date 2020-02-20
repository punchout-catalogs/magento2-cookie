#1. Get current module version
setup_version=$(grep -o 'setup_version=".*"' ./etc/module.xml)
setup_version=${setup_version:15}
setup_version_len=${#setup_version}-1
setup_version=${setup_version:0:$setup_version_len}
echo $setup_version

#2. Pack current extension
module_file="../punchout_cookie-$setup_version.zip"
rm -rf $module_file
echo "Prepare Package: $module_file"

zip -r $module_file ./ \
-x "*.git*" \
-x "*.idea*" \
-x "*prepare_package.sh*" \
-x "*.DS_Store*"

echo "Packed: $module_file"