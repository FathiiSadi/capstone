#include <iostream>
using namespace std;

int main(){
    int t; cin >> t;
    while (t--)
    {
        int n; cin >> n;
        int ans=10;
        while (n > 10)
        {
            int digit = n % 10;
            if (digit < ans) {
                ans = digit;
            }
            n /= 10;
        }
        cout<<ans<<endl;
       
    }
    

    return 0;
}