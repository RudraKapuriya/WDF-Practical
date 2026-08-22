#include<iostream>
using namespace std;
int main()
{
    int i,n;
    cout<<"Enter the No. of Marks:";
    cin>>n;
    int marks[n];
    cout<<"Enter the values of Marks:\n"<<endl;
    for(i=0;i<n;i++)
    {
        cin>>marks[i];
    }
    for(int j=0;j<n;j++)
    {
        for(i=0;i<(n-j);i++)
        {
            if(marks[i]>marks[i+1])
            {
                int temp=marks[i];
                marks[i]=marks[i+1];
                marks[i+1]=temp;
            }
        }
    }
    cout<<"-------------"<<endl;
    cout<<"The Sorted Marks:";
    cout<<"[";
    for(i=0;i<n;i++)
    {
        cout<<marks[i]<<",";
    }
    cout<<"]"<<endl;
    return 0;
}
